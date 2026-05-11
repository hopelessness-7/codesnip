<?php

namespace App\Jobs;

use App\Models\Snippet;
use App\Modules\AI\Providers\OllamaProvider;
use App\Repositories\Eloquent\SnippetRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTagsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;
    public $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Snippet $snippet
    ){}

    /**
     * Execute the job.
     * @throws \Throwable
     */
    public function handle(SnippetRepository $repository): void
    {
        $tags = $this->fetchTagsFromOllama();

        if (empty($tags)) {
            $tags = $this->generateTagsLocally();
        }

        $repository->attachTags($this->snippet, $tags);
    }

    private function fetchTagsFromOllama(): array
    {
        try {
            $this->snippet->loadMissing('user');
            $model = ($this->snippet->user && is_string($this->snippet->user->ollama_model) && $this->snippet->user->ollama_model !== '')
                ? $this->snippet->user->ollama_model
                : (string) config('openai.ollama.model');

            $ollamaProvider = new OllamaProvider([
                'host' => config('openai.ollama.host'),
                'model' => $model,
            ]);

            $prompt = $this->buildPrompt();

            $tags = $ollamaProvider->generateJson($prompt, [
                'temperature' => 0.3,
                'num_predict' => 100,
            ]);

            // Преобразуем в нужный формат
            return array_map(function ($tag) {
                return [
                    'name' => is_string($tag) ? strtolower($tag) : strtolower($tag['name'] ?? ''),
                    'is_ai_generated' => true,
                ];
            }, $tags);

        } catch (\Exception $e) {
            Log::warning('Ollama fetch failed, using local generation', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function generateTagsLocally(): array
    {
        $tags = [];
        $code = $this->snippet->code;
        $title = $this->snippet->title;
        $language = $this->snippet->language;

        // Язык как тег
        if ($language) {
            $tags[] = [
                'name' => strtolower($language),
                'is_ai_generated' => false,
            ];
        }

        // Паттерны для определения технологий
        $patterns = [
            'php' => ['/<\?php/', '/\$[a-z_]/', '/->/', '/::/'],
            'javascript' => ['/const /', '/let /', '/=>/', '/console\./'],
            'python' => ['/def /', '/import /', '/print\(/'],
            'sql' => ['/SELECT /i', '/INSERT /i', '/CREATE TABLE/i'],
            'bash' => ['/^#!/', '/echo /', '/\$[A-Z]/'],
            'html' => ['/<html/', '/<div/', '/<head>/'],
            'css' => ['/\{[\s\S]*\}/', '/@media/', '/\.\w+\s*{/'],
            'laravel' => ['/Illuminate/', '/Eloquent/', '/Route::/'],
            'vue' => ['/Vue\./', '/createApp/', '/ref\(/'],
            'react' => ['/React\./', '/useState/', '/useEffect/'],
            'api' => ['/api/', '/request\(/', '/response/'],
            'database' => ['/database/', '/migration/', '/query/'],
            'testing' => ['/test/', '/assert/', '/phpunit/'],
        ];

        foreach ($patterns as $tag => $patternList) {
            if ($tag === $language) {
                continue;
            }

            foreach ($patternList as $pattern) {
                if (preg_match($pattern, $code)) {
                    $tags[] = [
                        'name' => $tag,
                        'is_ai_generated' => false,
                    ];
                    break;
                }
            }
        }

        // Теги из названия
        $titleWords = explode(' ', strtolower($title));
        $commonTerms = ['crud', 'api', 'auth', 'login', 'search', 'filter', 'sort', 'upload'];

        foreach ($titleWords as $word) {
            $word = trim($word, ',.!?;:()[]{}');
            if (in_array($word, $commonTerms)) {
                $tags[] = [
                    'name' => $word,
                    'is_ai_generated' => false,
                ];
            }
        }

        return array_values(
            array_intersect_key(
                $tags,
                array_unique(array_column($tags, 'name'))
            )
        );
    }

    private function buildPrompt(): string
    {
        $code = $this->snippet->code;
        $title = $this->snippet->title;
        $language = $this->snippet->language ?? '';

        return <<<PROMPT
            Analyze this code and return ONLY a JSON array of relevant tags (languages, frameworks, patterns, technologies).

            Title: {$title}
            Language: {$language}
            Code: {$code}

            Return ONLY the JSON array, nothing else. Example: ["php", "laravel", "eloquent"]
        PROMPT;
    }
}
