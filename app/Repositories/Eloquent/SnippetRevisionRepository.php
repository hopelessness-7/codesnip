<?php

namespace App\Repositories\Eloquent;

use App\Models\SnippetRevision;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\SnippetRevisionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

readonly class SnippetRevisionRepository extends BaseRepository implements SnippetRevisionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SnippetRevision());
    }

    public function getVersionsSnippet(int $id): Collection|array
    {
        return $this->querySnippet($id)->pluck('version');
    }

    public function getMaxVersionsSnippet(int $id): int
    {
        return (int) $this->querySnippet($id)->max('version');
    }

    public function getSnippetRevisions(int $id): Collection
    {
        return $this->querySnippet($id)
            ->orderByDesc('version')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findRevisionForSnippet(int $snippetId, int $revisionId): ?Model
    {
        return $this->querySnippet($snippetId)->find($revisionId);
    }

    public function getPreviousRevisionForSnippet(int $snippetId, int $version): ?Model
    {
        return $this->querySnippet($snippetId)
            ->where('version', '<', $version)
            ->orderByDesc('version')
            ->first();
    }

    protected function querySnippet(int $id): Builder
    {
        return $this->query()->where('snippet_id', $id);
    }
}
