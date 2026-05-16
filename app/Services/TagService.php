<?php

namespace App\Services;

use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TagService extends BaseService
{
    public function __construct(TagRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function paginateForUserSnippets(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginateForUserSnippets($userId, $perPage);
    }

    public function topForUserSnippets(int $userId, int $limit = 8): Collection
    {
        return $this->repository->topForUserSnippets($userId, $limit);
    }

    public function forUserSnippets(int $userId): Collection
    {
        return $this->repository->forUserSnippets($userId);
    }
}
