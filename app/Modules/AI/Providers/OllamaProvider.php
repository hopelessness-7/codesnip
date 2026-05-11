<?php

namespace App\Modules\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider extends AbstractAiProvider
{
    protected string $name = 'ollama';

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->name = 'ollama';
    }

    public function generate(string $prompt, array $options = []): string
    {
        try {
            $response = Http::timeout(90)
                ->post("{$this->config['host']}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => array_merge([
                        'temperature' => 0.7,
                        'num_predict' => 500,
                    ], $options),
                ]);

            if ($response->successful()) {
                return trim($response->json()['response'] ?? '');
            }

            Log::error('Ollama API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Ollama API error: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('Ollama generate failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $prompt = $this->formatMessages($messages);
        return $this->generate($prompt, $options);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->config['host']}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Locally installed model names from Ollama (`GET /api/tags`).
     *
     * @return list<string>
     */
    public function listLocalModelNames(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->config['host']}/api/tags");
            if (! $response->successful()) {
                return [];
            }

            $models = $response->json('models');
            if (! is_array($models)) {
                return [];
            }

            return collect($models)
                ->pluck('name')
                ->filter(fn ($name) => is_string($name) && $name !== '')
                ->map(fn (string $name) => $name)
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Models currently loaded in memory (`GET /api/ps`).
     *
     * @return list<string>
     */
    public function listRunningModelNames(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->config['host']}/api/ps");
            if (! $response->successful()) {
                return [];
            }

            $models = $response->json('models');
            if (! is_array($models)) {
                return [];
            }

            return collect($models)
                ->pluck('name')
                ->filter(fn ($name) => is_string($name) && $name !== '')
                ->map(fn (string $name) => $name)
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
