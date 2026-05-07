<?php

namespace App\Services;

use App\Models\Folder;
use App\Repositories\Eloquent\FolderRepository;
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

    public function findForUser(int $userId, int $folderId): ?Folder
    {
        return $this->repository->findForUser($userId, $folderId);
    }
}
