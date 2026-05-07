<?php

namespace App\Modules\AI\Providers;

use App\Modules\AI\Contracts\AiProviderInterface;

abstract class AbstractAiProvider implements AiProviderInterface
{
    protected string $name;
    protected string $model;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'default';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function generateJson(string $prompt, array $options = []): array
    {
        try {
            $response = $this->generate($prompt, $options);
            return $this->parseJsonResponse($response);
        } catch (\Exception $e) {
            \Log::error("{$this->name}: JSON generation failed", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function parseJsonResponse(string $response)
    {
        // Пробуем найти JSON в ответе
        if (preg_match('/```(?:json)?\s*(\[.*?\]|\{.*?\})\s*```/s', $response, $matches)) {
            return json_decode($matches[1], true) ?? [];
        }

        if (preg_match('/(\[.*?\]|\{.*?\})/s', $response, $matches)) {
            return json_decode($matches[1], true) ?? [];
        }

        // Пробуем весь ответ как JSON
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        \Log::warning("{$this->name}: Failed to parse JSON", [
            'response' => $response,
        ]);

        return [];
    }
}
