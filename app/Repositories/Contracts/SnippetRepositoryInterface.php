<?php

namespace App\Repositories\Contracts;

use App\DTOs\SearchFilters;
use App\DTOs\SnippetData;
use App\Models\Snippet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SnippetRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUuid(string $uuid): ?Snippet;

    public function findByUser(int $userId, SearchFilters $filters): LengthAwarePaginator;

    public function search(string $query, ?int $userId = null): LengthAwarePaginator;

    public function getByTag(string $tag, ?int $userId = null): LengthAwarePaginator;

    public function getPublicSnippets(SearchFilters $filters): LengthAwarePaginator;

    public function attachTags(Snippet $snippet, array $tags): void;

    public function syncTags(Snippet $snippet, array $tags): void;

    public function getPopularTags(?int $userId = null, int $limit = 20): Collection;
}
