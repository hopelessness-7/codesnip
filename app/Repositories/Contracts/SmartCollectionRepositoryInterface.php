<?php

namespace App\Repositories\Contracts;

use App\Models\SmartCollection;
use Illuminate\Support\Collection;

interface SmartCollectionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId): Collection;
    public function findForUser(int $userId, int $collectionId): ?SmartCollection;
}
