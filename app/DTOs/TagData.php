<?php

namespace App\DTOs;

final readonly class TagData
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public bool $is_ai_generated = false,
        public ?string $description = null,
        public ?string $category = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'is_ai_generated' => $this->is_ai_generated,
        ];
    }
}
