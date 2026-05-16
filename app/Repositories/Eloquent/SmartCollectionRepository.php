<?php

namespace App\Repositories\Eloquent;

use App\Models\SmartCollection;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\SmartCollectionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

readonly class SmartCollectionRepository extends BaseRepository implements SmartCollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SmartCollection());
    }

    public function findByUser(int $userId): Collection
    {
        return $this->query()->where('user_id', $userId)->orderBy('name')->get();
    }

    public function paginateByUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $userId)
            ->withCount('snippets')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findForUser(int $userId, int $collectionId): ?SmartCollection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('id', $collectionId)
            ->first();
    }

    public function countByUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->count();
    }
}
