<?php

namespace App\DTOs;

final class SearchFilters extends BaseDTO
{
    public function __construct(
        public string $query = '',
        public array $tags = [],
        public ?string $language = null,
        public ?int $ownerId = null,
        public ?string $createdFrom = null,
        public ?string $createdTo = null,
        public ?string $updatedFrom = null,
        public ?string $updatedTo = null,
        public array $folderIds = [],
        public ?int $smartCollectionId = null,
        public string $sortBy = 'updated_at',
        public string $sortDirection = 'desc',
        public int $perPage = 15,
        public int $page = 1,
        public ?bool $isPublic = null,
    ) {}

    public static function fromArray(array $data): static
    {
        $tags = $data['tags'] ?? [];
        if (! is_array($tags)) {
            $tags = [];
        }
        $folderIds = $data['folder_ids'] ?? [];
        if (! is_array($folderIds)) {
            $folderIds = [];
        }

        $sortDirection = strtolower((string) ($data['sort_direction'] ?? 'desc'));

        $isPublic = null;
        if (array_key_exists('is_public', $data) && $data['is_public'] !== null && $data['is_public'] !== '') {
            $isPublic = filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new static(
            query: isset($data['query']) ? trim((string) $data['query']) : '',
            tags: array_values(array_filter(array_map('strval', $tags))),
            language: isset($data['language']) && $data['language'] !== '' && $data['language'] !== null
                ? (string) $data['language']
                : null,
            ownerId: isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : null,
            createdFrom: isset($data['created_from']) && $data['created_from'] !== '' ? (string) $data['created_from'] : null,
            createdTo: isset($data['created_to']) && $data['created_to'] !== '' ? (string) $data['created_to'] : null,
            updatedFrom: isset($data['updated_from']) && $data['updated_from'] !== '' ? (string) $data['updated_from'] : null,
            updatedTo: isset($data['updated_to']) && $data['updated_to'] !== '' ? (string) $data['updated_to'] : null,
            folderIds: array_values(array_filter(array_map('intval', $folderIds), fn (int $id) => $id > 0)),
            smartCollectionId: isset($data['smart_collection_id']) && $data['smart_collection_id'] !== ''
                ? (int) $data['smart_collection_id']
                : null,
            sortBy: (string) ($data['sort_by'] ?? 'updated_at'),
            sortDirection: $sortDirection === 'asc' ? 'asc' : 'desc',
            perPage: max(1, min(100, (int) ($data['per_page'] ?? 15))),
            page: max(1, (int) ($data['page'] ?? 1)),
            isPublic: $isPublic,
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'tags' => $this->tags,
            'language' => $this->language,
            'owner_id' => $this->ownerId,
            'created_from' => $this->createdFrom,
            'created_to' => $this->createdTo,
            'updated_from' => $this->updatedFrom,
            'updated_to' => $this->updatedTo,
            'folder_ids' => $this->folderIds,
            'smart_collection_id' => $this->smartCollectionId,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
            'page' => $this->page,
            'is_public' => $this->isPublic,
        ];
    }
}
