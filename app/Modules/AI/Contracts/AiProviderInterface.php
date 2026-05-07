<?php

namespace App\Modules\AI\Contracts;

interface AiProviderInterface
{
    /**
     * Сгенерировать ответ.
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Сгенерировать чат-ответ.
     */
    public function chat(array $messages, array $options = []): string;

    /**
     * Сгенерировать JSON-ответ.
     */
    public function generateJson(string $prompt, array $options = []): array;

    /**
     * Получить имя провайдера.
     */
    public function getName(): string;

    /**
     * Получить используемую модель.
     */
    public function getModel(): string;

    /**
     * Проверить доступность провайдера.
     */
    public function isAvailable(): bool;
}
