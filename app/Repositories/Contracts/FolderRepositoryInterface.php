<?php

namespace App\Repositories\Contracts;

use App\Models\Folder;
use Illuminate\Support\Collection;

interface FolderRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId): Collection;
    public function findForUser(int $userId, int $folderId): ?Folder;
    public function countByUser(int $userId): int;
}
