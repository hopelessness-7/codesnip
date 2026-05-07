<?php

namespace App\Modules\AI\DTO;

readonly class AiRequest
{
    public function __construct(
        public string $prompt,
        public string $systemPrompt = '',
        public array  $context = [],
        public array  $options = [],
    ) {}
}
