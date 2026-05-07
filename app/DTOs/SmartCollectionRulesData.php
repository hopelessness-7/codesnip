<?php

namespace App\DTOs;

use App\Enums\SnippetLanguage;

final class SmartCollectionRulesData extends BaseDTO
{
    public function __construct(
        public ?SnippetLanguage $language = null,
        public ?bool $is_public = null,
        public array $tags = [],
        public string $tags_mode = 'all',
        public string $query = '',
        public ?string $created_from = null,
        public ?string $created_to = null,
        public ?string $updated_from = null,
        public ?string $updated_to = null
    ){}

    public static function fromArray(array $data): static
    {
        $tags = $data['tags'] ?? [];

        if (!is_array($tags)) {
            $tags = [];
        }

        return new self(
            language: isset($data['language'])
                ? (is_string($data['language'])
                    ? SnippetLanguage::tryFrom($data['language'])
                    : $data['language'])
                : null,
            is_public: ! array_key_exists('is_public', $data) || $data['is_public'] === null || $data['is_public'] === ''
                ? null
                : filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            tags: array_values(array_filter(array_map(fn ($v) => mb_strtolower(trim((string) $v)), $tags))),
            tags_mode: in_array(($data['tags_mode'] ?? 'all'), ['all', 'any'], true) ? $data['tags_mode'] : 'all',
            query: (string) ($data['query'] ?? ''),
            created_from: $data['created_from'] ?? null,
            created_to: $data['created_to'] ?? null,
            updated_from: $data['updated_from'] ?? null,
            updated_to: $data['updated_to'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'language' => $this->language?->value,
            'is_public' => $this->is_public,
            'tags' => $this->tags,
            'tags_mode' => $this->tags_mode,
            'query' => $this->query,
            'created_from' => $this->created_from,
            'created_to' => $this->created_to,
            'updated_from' => $this->updated_from,
            'updated_to' => $this->updated_to,
        ];
    }
}
