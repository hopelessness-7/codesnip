<?php

namespace App\Repositories\Contracts;

use App\Models\SmartCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SmartCollectionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId): Collection;

    public function paginateByUser(int $userId, int $perPage): LengthAwarePaginator;

    public function findForUser(int $userId, int $collectionId): ?SmartCollection;

    public function countByUser(int $userId): int;
}
