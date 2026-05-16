<?php

namespace App\Repositories\Contracts;

use App\Models\Folder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FolderRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId): Collection;

    public function paginateByUser(int $userId, int $perPage): LengthAwarePaginator;

    public function findForUser(int $userId, int $folderId): ?Folder;

    public function countByUser(int $userId): int;
}
