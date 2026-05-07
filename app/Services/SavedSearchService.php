<?php

namespace App\Services;

use App\Models\SavedSearch;
use Illuminate\Support\Collection;

class SavedSearchService
{
    public function listForUser(int $userId): Collection
    {
        return SavedSearch::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function createForUser(int $userId, string $name, array $filters): SavedSearch
    {
        return SavedSearch::query()->create([
            'user_id' => $userId,
            'name' => trim($name),
            'filters_json' => $filters,
        ]);
    }

    public function deleteForUser(int $userId, int $savedSearchId): bool
    {
        $savedSearch = SavedSearch::query()
            ->where('id', $savedSearchId)
            ->where('user_id', $userId)
            ->first();

        if (! $savedSearch) {
            return false;
        }

        return (bool) $savedSearch->delete();
    }

    public function findForUser(int $userId, int $savedSearchId): ?SavedSearch
    {
        return SavedSearch::query()
            ->where('id', $savedSearchId)
            ->where('user_id', $userId)
            ->first();
    }
}
