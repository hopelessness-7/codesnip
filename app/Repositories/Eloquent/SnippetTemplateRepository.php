<?php

namespace App\Repositories\Eloquent;

use App\Models\SnippetTemplate;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\SnippetTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class SnippetTemplateRepository extends BaseRepository implements SnippetTemplateRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SnippetTemplate());
    }

    public function findByUser(int $userId): Collection
    {
        return $this->baseQueryByUser($userId)->get();
    }

    public function findForUser(int $userId, int $templateId): ?SnippetTemplate
    {
        return $this->baseQueryByUser($userId)->find($templateId);
    }

    public function countByUser(int $userId): int
    {
        return $this->baseQueryByUser($userId)->count();
    }

    public function baseQueryByUser(int $userId): Builder
    {
        return $this->query()->where('user_id', $userId);
    }
}
