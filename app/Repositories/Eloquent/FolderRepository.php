<?php

namespace App\Repositories\Eloquent;

use App\Models\Folder;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\FolderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

readonly class FolderRepository extends BaseRepository implements FolderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Folder());
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

    public function findForUser(int $userId, int $folderId): ?Folder
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('id', $folderId)
            ->first();
    }

    public function countByUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->count();
    }
}
