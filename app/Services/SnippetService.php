<?php

namespace App\Services;

use App\DTOs\SearchFilters;
use App\Jobs\GenerateTagsJob;
use App\Models\Snippet;
use App\Repositories\Eloquent\SnippetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class SnippetService extends BaseService
{
    /** @var array<int, string> */
    private array $pendingTags = [];

    private bool $shouldSyncTags = false;

    public function __construct(
        SnippetRepository $repository,
        private readonly SnippetRevisionService $snippetRevisionService,
        private readonly SmartCollectionService $smartCollectionService
    ) {
        $this->repository = $repository;
    }

    public function findByUser(int $userId, SearchFilters $filters): LengthAwarePaginator
    {
        return $this->repository->findByUser($userId, $filters);
    }

    public function findByUuid(string $uuid): ?Snippet
    {
        return $this->repository->findByUuid($uuid);
    }

    public function generatePublicLink(int $id): array
    {
        $snippet = $this->find($id);
        $uuid = $snippet->uuid;

        $signedUrl = \URL::temporarySignedRoute(
            'snippets.public',
            now()->addDays(7),
            ['uuid' => $uuid]
        );

        return [
            'uuid' => $uuid,
            'url' => $signedUrl,
            'expires_at' => now()->addDays(7)->toDateTimeString(),
        ];
    }

    public function rollbackToRevision(Snippet $snippet, int $revisionId): bool
    {
        $revision = $this->snippetRevisionService->getRevisionForSnippet($snippet, $revisionId);

        if (! $revision) {
            return false;
        }

        $tags = collect($revision['tags_json'] ?? [])
            ->map(function ($tag): ?string {
                if (is_array($tag)) {
                    return $tag['name'] ?? $tag['slug'] ?? null;
                }

                return is_string($tag) ? $tag : null;
            })
            ->filter()
            ->values()
            ->all();

        $this->update($snippet, [
            'title' => (string) $revision['title'],
            'code' => (string) $revision['code'],
            'language' => (string) $revision['language'],
            'is_public' => (bool) $revision['is_public'],
            'tags' => $tags,
            'user_id' => $snippet->user_id,
        ]);

        return true;
    }

    public function buildRevisionDiff(Snippet $snippet, int $revisionId): array
    {
        return $this->snippetRevisionService->buildRevisionDiff($snippet, $revisionId);
    }

    public function buildRevisionDiffWithPrevious(Snippet $snippet, int $revisionId): array
    {
        return $this->snippetRevisionService->buildRevisionDiffWithPrevious($snippet, $revisionId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function afterCreate(Model $model, array $data): void
    {
        /** @var Snippet $model */
        $tags = $this->extractTags($data);
        $folderIds = $this->extractFolderIds($data);

        if ($tags !== []) {
            $this->repository->syncTags($model, $tags);
        } else {
            GenerateTagsJob::dispatch($model);
        }

        $model->folders()->sync($folderIds);
        $this->smartCollectionService->refreshMembershipForSnippet($model->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        $this->shouldSyncTags = array_key_exists('tags', $data);
        $this->pendingTags = $this->extractTags($data);

        if (! $this->snippetRevisionService->hasMeaningfulChanges($model, $data, $this->shouldSyncTags, $this->pendingTags)) {
            unset($data['tags']);

            return;
        }
        $this->snippetRevisionService->createSnapshot($model, (int) auth()->id());

        unset($data['tags']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function afterUpdate(Model $model, array $data): void
    {
        if ($this->shouldSyncTags) {
            /** @var Snippet $model */
            $this->repository->syncTags($model, $this->pendingTags);
        }
        /** @var Snippet $model */
        $model->folders()->sync($this->extractFolderIds($data));
        $this->smartCollectionService->refreshMembershipForSnippet($model->id);

        $this->pendingTags = [];
        $this->shouldSyncTags = false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function extractTags(array $data): array
    {
        return collect($data['tags'] ?? [])
            ->filter(fn ($t) => $t !== null && $t !== '')
            ->map(fn ($t) => (string) $t)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private function extractFolderIds(array $data): array
    {
        return collect($data['folder_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

}
