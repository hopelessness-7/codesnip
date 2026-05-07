<?php

namespace App\DTOs;

use App\Enums\SnippetLanguage;
use App\Models\Snippet;
use Illuminate\Support\Collection;

final class SnippetData extends BaseDTO
{
    public function __construct(
        public string $title,
        public string $code,
        public ?SnippetLanguage $language = null,
        /** @var Collection<int, string> */
        public Collection $tags = new Collection(),
        public bool $is_public = false,
        public ?int $user_id = null,
        public ?string $uuid = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            title: $data['title'] ?? '',
            code: $data['code'] ?? '',
            language: isset($data['language'])
                ? (is_string($data['language'])
                    ? SnippetLanguage::tryFrom($data['language']) ?? SnippetLanguage::UNKNOWN
                    : $data['language'])
                : null,
            tags: collect($data['tags'] ?? []),
            is_public: (bool) ($data['is_public'] ?? false),
            user_id: $data['user_id'] ?? auth()->id(),
            uuid: $data['uuid'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language?->value,
            'tags' => $this->tags->toArray(),
            'is_public' => $this->is_public,
            'user_id' => $this->user_id,
            'uuid' => $this->uuid,
        ];
    }

    /**
     * Создать DTO из модели Snippet
     */
    public static function fromModel(Snippet $snippet): static
    {
        return new self(
            title: $snippet->title,
            code: $snippet->code,
            language: $snippet->language instanceof SnippetLanguage
                ? $snippet->language
                : SnippetLanguage::tryFrom((string) $snippet->language),
            tags: $snippet->tags->pluck('name'),
            is_public: $snippet->is_public,
            user_id: $snippet->user_id,
            uuid: $snippet->uuid,
        );
    }
}
