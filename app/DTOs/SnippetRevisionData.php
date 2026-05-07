<?php

namespace App\DTOs;

use App\Enums\SnippetLanguage;

class SnippetRevisionData extends BaseDTO
{
    public function __construct(
        public int $snippet_id,
        public int $created_by,
        public int $version,
        public string $title,
        public string $code,
        public ?SnippetLanguage $language = null,
        public bool $is_public = false,
        public ?array $tags_json = [],
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            snippet_id: $data['snippet_id'],
            created_by: $data['created_by'],
            version: $data['version'],
            title: $data['title'],
            code: $data['code'],
            language: isset($data['language'])
                ? (is_string($data['language'])
                    ? SnippetLanguage::tryFrom($data['language']) ?? SnippetLanguage::UNKNOWN
                    : $data['language'])
                : null,
            is_public: (bool) ($data['is_public'] ?? false),
            tags_json: $data['tags_json'] ?? [],
        );
    }

    public function toArray(): array
    {
       return [
           'snippet_id' => $this->snippet_id,
           'created_by' => $this->created_by,
           'version' => $this->version,
           'title' => $this->title,
           'code' => $this->code,
           'language' => $this->language?->value,
           'is_public' => $this->is_public,
           'tags_json' => $this->tags_json,
       ];
    }
}
