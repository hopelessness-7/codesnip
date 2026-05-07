<?php

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Support\Collection;

interface TagRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name): ?Tag;

    public function findOrCreate(string $name, bool $isAiGenerated = false): Tag;

    public function getPopular(int $limit = 20): Collection;

    public function searchByName(string $query, int $limit = 10): Collection;

    public function getTagsForSnippet(int $snippetId): Collection;

    /**
     * Tags attached to at least one snippet owned by the user, with per-user usage count.
     */
    public function forUserSnippets(int $userId): Collection;
}
