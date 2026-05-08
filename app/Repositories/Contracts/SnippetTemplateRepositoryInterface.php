<?php

namespace App\Repositories\Contracts;

use App\Models\SnippetTemplate;
use Illuminate\Support\Collection;

interface SnippetTemplateRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId): Collection;
    public function findForUser(int $userId, int $templateId): ?SnippetTemplate;
    public function countByUser(int $userId): int;
}
