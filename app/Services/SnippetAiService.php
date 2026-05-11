<?php

namespace App\Services;

use App\Models\Snippet;
use App\Modules\AI\Providers\OllamaProvider;

class SnippetAiService
{
    /**
     * @return array{ok:bool,content:string,error?:string}
     */
    public function generateSummary(Snippet $snippet): array
    {
        return $this->generateText($this->providerForSnippet($snippet), $this->buildSummaryPrompt($snippet), [
            'temperature' => 0.4,
            'num_predict' => 220,
        ]);
    }

    /**
     * @return array{ok:bool,content:string,error?:string}
     */
    public function explainCode(Snippet $snippet): array
    {
        return $this->generateText($this->providerForSnippet($snippet), $this->buildExplainPrompt($snippet), [
            'temperature' => 0.35,
            'num_predict' => 700,
        ]);
    }

    /**
     * @return array{ok:bool,content:string,error?:string}
     */
    public function generateTest(Snippet $snippet): array
    {
        return $this->generateText($this->providerForSnippet($snippet), $this->buildTestPrompt($snippet), [
            'temperature' => 0.45,
            'num_predict' => 900,
        ]);
    }

    private function providerForSnippet(Snippet $snippet): OllamaProvider
    {
        $snippet->loadMissing('user');
        $model = ($snippet->user && is_string($snippet->user->ollama_model) && $snippet->user->ollama_model !== '')
            ? $snippet->user->ollama_model
            : (string) config('openai.ollama.model');

        return new OllamaProvider([
            'host' => (string) config('openai.ollama.host'),
            'model' => $model,
        ]);
    }

    /**
     * @return array{ok:bool,content:string,error?:string}
     */
    private function generateText(OllamaProvider $provider, string $prompt, array $options): array
    {
        try {
            if (! $provider->isAvailable()) {
                return [
                    'ok' => false,
                    'content' => '',
                    'error' => 'AI provider is unavailable.',
                ];
            }

            $content = trim($provider->generate($prompt, $options));

            if ($content === '') {
                return [
                    'ok' => false,
                    'content' => '',
                    'error' => 'AI returned an empty response.',
                ];
            }

            return [
                'ok' => true,
                'content' => $content,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'content' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildSummaryPrompt(Snippet $snippet): string
    {
        $language = (string) ($snippet->language ?? 'unknown');
        $title = (string) $snippet->title;
        $code = (string) $snippet->code;

        return <<<PROMPT
Write a short practical summary of this code snippet.

Requirements:
- 2-4 bullet points.
- Focus on purpose and where this can be used.
- Keep it concise and clear.

Title: {$title}
Language: {$language}
Code:
{$code}
PROMPT;
    }

    private function buildExplainPrompt(Snippet $snippet): string
    {
        $language = (string) ($snippet->language ?? 'unknown');
        $title = (string) $snippet->title;
        $code = (string) $snippet->code;

        return <<<PROMPT
Explain this code for a developer.

Requirements:
- Brief overview first.
- Then explain key parts step by step.
- Mention possible risks or edge cases if any.
- Keep the tone practical.

Title: {$title}
Language: {$language}
Code:
{$code}
PROMPT;
    }

    private function buildTestPrompt(Snippet $snippet): string
    {
        $language = (string) ($snippet->language ?? 'unknown');
        $code = (string) $snippet->code;

        return <<<PROMPT
Generate a useful test example for this code.

Requirements:
- Return only code in one fenced code block.
- Use the most appropriate testing style/framework for the language.
- Include realistic assertions.

Language: {$language}
Code under test:
{$code}
PROMPT;
    }
}
