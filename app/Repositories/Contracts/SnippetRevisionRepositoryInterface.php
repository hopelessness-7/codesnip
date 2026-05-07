<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface SnippetRevisionRepositoryInterface extends BaseRepositoryInterface
{
    public function getVersionsSnippet(int $id): Collection|array;

    public function getMaxVersionsSnippet(int $id): int;

    public function getSnippetRevisions(int $id): Collection;

    public function findRevisionForSnippet(int $snippetId, int $revisionId);

    public function getPreviousRevisionForSnippet(int $snippetId, int $version);
}
