<?php

namespace App\Services;

use App\DTOs\SnippetRevisionData;
use App\Models\Snippet;
use App\Models\SnippetRevision;
use App\Repositories\Eloquent\SnippetRevisionRepository;

readonly class SnippetRevisionService
{
    public function __construct(
        private SnippetRevisionRepository $snippetRevisionRepository
    ) {}

    public function createSnapshot(Snippet $snippet, int $createdBy): void
    {
        $snapshot = SnippetRevisionData::fromArray([
            'snippet_id' => $snippet->id,
            'created_by' => $createdBy,
            'version' => $this->snippetRevisionRepository->getMaxVersionsSnippet($snippet->id) + 1,
            'title' => $snippet->title,
            'code' => $snippet->code,
            'language' => (string) $snippet->language,
            'is_public' => (bool) $snippet->is_public,
            'tags_json' => $snippet->tags()->pluck('name')->all(),
        ]);

        $this->snippetRevisionRepository->createFromDTO($snapshot);
    }

    public function hasMeaningfulChanges(Snippet $snippet, array $data, bool $tagsProvided, array $pendingTags): bool
    {
        $nextTitle = array_key_exists('title', $data) ? (string) $data['title'] : (string) $snippet->title;
        $nextCode = array_key_exists('code', $data) ? (string) $data['code'] : (string) $snippet->code;
        $nextLanguage = array_key_exists('language', $data) ? (string) $data['language'] : (string) $snippet->language;
        $nextVisibility = array_key_exists('is_public', $data) ? (bool) $data['is_public'] : (bool) $snippet->is_public;

        if (
            $nextTitle !== (string) $snippet->title
            || $nextCode !== (string) $snippet->code
            || $nextLanguage !== (string) $snippet->language
            || $nextVisibility !== (bool) $snippet->is_public
        ) {
            return true;
        }

        if (! $tagsProvided) {
            return false;
        }

        $currentTags = $snippet->tags()->pluck('name')->values()->all();

        return $currentTags !== $pendingTags;
    }

    public function getRevisionsForSnippet(Snippet $snippet): array
    {
        return $this->snippetRevisionRepository->getSnippetRevisions($snippet->id)
            ->map(fn (SnippetRevision $revision): array => [
                'id' => $revision->id,
                'version' => $revision->version,
                'title' => $revision->title,
                'language' => $revision->language,
                'is_public' => (bool) $revision->is_public,
                'created_at' => $revision->created_at?->toDateTimeString(),
                'created_by' => $revision->created_by,
            ])
            ->all();
    }

    public function getRevisionForSnippet(Snippet $snippet, int $revisionId): ?array
    {
        /** @var SnippetRevision|null $revision */
        $revision = $this->snippetRevisionRepository->findRevisionForSnippet($snippet->id, $revisionId);

        if (! $revision) {
            return null;
        }

        return [
            'id' => $revision->id,
            'version' => $revision->version,
            'title' => $revision->title,
            'code' => $revision->code,
            'language' => $revision->language,
            'is_public' => (bool) $revision->is_public,
            'tags_json' => $revision->tags_json ?? [],
            'created_at' => $revision->created_at?->toDateTimeString(),
            'created_by' => $revision->created_by,
        ];
    }

    public function getPreviousRevisionForSnippet(Snippet $snippet, int $version): ?array
    {
        /** @var SnippetRevision|null $previous */
        $previous = $this->snippetRevisionRepository->getPreviousRevisionForSnippet($snippet->id, $version);

        if (! $previous) {
            return null;
        }

        return [
            'id' => $previous->id,
            'version' => $previous->version,
            'title' => $previous->title,
            'code' => $previous->code,
            'language' => $previous->language,
            'is_public' => (bool) $previous->is_public,
            'tags_json' => $previous->tags_json ?? [],
            'created_at' => $previous->created_at?->toDateTimeString(),
            'created_by' => $previous->created_by,
        ];
    }

    public function buildRevisionDiff(Snippet $snippet, int $revisionId): array
    {
        $revision = $this->getRevisionForSnippet($snippet, $revisionId);
        if (! $revision) {
            return $this->emptyDiff();
        }

        $currentPayload = [
            'title' => $snippet->title,
            'code' => (string) $snippet->code,
            'language' => (string) $snippet->language,
            'is_public' => (bool) $snippet->is_public,
            'tags_json' => $snippet->tags()->pluck('name')->values()->all(),
        ];

        return $this->buildDiffFromPayloads($currentPayload, $revision);
    }

    public function buildRevisionDiffWithPrevious(Snippet $snippet, int $revisionId): array
    {
        $revision = $this->getRevisionForSnippet($snippet, $revisionId);
        if (! $revision) {
            return $this->emptyDiff();
        }

        $previous = $this->getPreviousRevisionForSnippet($snippet, (int) $revision['version']);
        if (! $previous) {
            return $this->emptyDiff();
        }

        return $this->buildDiffFromPayloads($previous, $revision);
    }

    private function emptyDiff(): array
    {
        return [
            'has_changes' => false,
            'fields' => [],
            'code' => [
                'changed' => false,
                'lines' => [],
                'summary' => ['added' => 0, 'removed' => 0, 'changed' => 0],
                'current_line_count' => 0,
                'revision_line_count' => 0,
            ],
        ];
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(function ($tag): ?string {
                if (is_array($tag)) {
                    return $tag['name'] ?? $tag['slug'] ?? null;
                }

                return is_string($tag) ? $tag : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildDiffFromPayloads(array $currentPayload, array $revisionPayload): array
    {
        $currentTags = $this->normalizeTags($currentPayload['tags_json'] ?? []);
        $revisionTags = $this->normalizeTags($revisionPayload['tags_json'] ?? []);

        $fields = [
            'title' => [
                'label_key' => 'snippets.revisions.field_title',
                'changed' => (string) ($currentPayload['title'] ?? '') !== (string) ($revisionPayload['title'] ?? ''),
                'current' => (string) ($currentPayload['title'] ?? ''),
                'revision' => (string) ($revisionPayload['title'] ?? ''),
            ],
            'language' => [
                'label_key' => 'snippets.revisions.field_language',
                'changed' => (string) ($currentPayload['language'] ?? '') !== (string) ($revisionPayload['language'] ?? ''),
                'current' => (string) ($currentPayload['language'] ?? ''),
                'revision' => (string) ($revisionPayload['language'] ?? ''),
            ],
            'visibility' => [
                'label_key' => 'snippets.revisions.field_visibility',
                'changed' => (bool) ($currentPayload['is_public'] ?? false) !== (bool) ($revisionPayload['is_public'] ?? false),
                'current' => (bool) ($currentPayload['is_public'] ?? false) ? 'public' : 'private',
                'revision' => (bool) ($revisionPayload['is_public'] ?? false) ? 'public' : 'private',
            ],
            'tags' => [
                'label_key' => 'snippets.revisions.field_tags',
                'changed' => $currentTags !== $revisionTags,
                'current' => implode(', ', $currentTags),
                'revision' => implode(', ', $revisionTags),
            ],
        ];

        $currentLines = preg_split('/\R/', (string) ($currentPayload['code'] ?? '')) ?: [];
        $revisionLines = preg_split('/\R/', (string) ($revisionPayload['code'] ?? '')) ?: [];
        $maxLines = max(count($currentLines), count($revisionLines));

        $diffLines = [];
        $summary = ['added' => 0, 'removed' => 0, 'changed' => 0];

        for ($i = 0; $i < $maxLines; $i++) {
            $currentLine = $currentLines[$i] ?? '';
            $revisionLine = $revisionLines[$i] ?? '';

            if (! array_key_exists($i, $currentLines)) {
                $summary['removed']++;
                $diffLines[] = ['line' => $i + 1, 'type' => 'removed', 'current' => '', 'revision' => $revisionLine];
                continue;
            }

            if (! array_key_exists($i, $revisionLines)) {
                $summary['added']++;
                $diffLines[] = ['line' => $i + 1, 'type' => 'added', 'current' => $currentLine, 'revision' => ''];
                continue;
            }

            if ($currentLine !== $revisionLine) {
                $summary['changed']++;
                $diffLines[] = ['line' => $i + 1, 'type' => 'changed', 'current' => $currentLine, 'revision' => $revisionLine];
            }
        }

        $hasCodeChanges = ($summary['added'] + $summary['removed'] + $summary['changed']) > 0;

        return [
            'has_changes' => collect($fields)->contains(fn (array $f): bool => (bool) $f['changed']) || $hasCodeChanges,
            'fields' => $fields,
            'code' => [
                'changed' => $hasCodeChanges,
                'lines' => $diffLines,
                'summary' => $summary,
                'current_line_count' => count($currentLines),
                'revision_line_count' => count($revisionLines),
            ],
        ];
    }
}
