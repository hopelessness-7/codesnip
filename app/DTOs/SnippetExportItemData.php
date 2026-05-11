<?php

namespace App\DTOs;

use App\DTOs\BaseDTO;
use App\Enums\SnippetLanguage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SnippetExportItemData extends BaseDTO
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $code,
        public ?SnippetLanguage $language = null,
        /** @var Collection<int, string> */
        public Collection $tags = new Collection(),
        public bool $is_public = false,
    ) {}

    public static function fromArray(array $data): static
    {
        $title = trim((string) ($data['title'] ?? ''));
        $rawLanguage = $data['language'] ?? null;

        return new self(
            uuid: (string) ($data['uuid'] ?? Str::uuid()->toString()),
            title: $title !== '' ? $title : 'Imported snippet',
            code: (string) ($data['code'] ?? ''),
            language: $rawLanguage instanceof SnippetLanguage
                ? $rawLanguage
                : (is_string($rawLanguage) ? SnippetLanguage::tryFrom($rawLanguage) : null),
            tags: collect($data['tags'] ?? [])
                ->map(fn ($tag) => trim((string) $tag))
                ->filter(fn (string $tag) => $tag !== '')
                ->unique()
                ->values(),
            is_public: (bool) ($data['is_public'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language?->value,
            'tags' => $this->tags->toArray(),
            'is_public' => $this->is_public,
        ];
    }
}
