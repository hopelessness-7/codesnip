<?php

namespace App\Services;

use App\Models\Folder;
use App\Repositories\Eloquent\FolderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FolderService extends BaseService
{
    public function __construct(FolderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listForUser(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }

    public function paginateForUser(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginateByUser($userId, $perPage);
    }

    public function findForUser(int $userId, int $folderId): ?Folder
    {
        return $this->repository->findForUser($userId, $folderId);
    }
}
