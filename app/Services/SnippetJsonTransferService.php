<?php

namespace App\Services;

use App\DTOs\ImportOptionsData;
use App\DTOs\ImportResultData;
use App\DTOs\SnippetArchiveEnvelopeData;
use App\DTOs\SnippetExportItemData;
use App\Models\Snippet;
use App\Models\User;
use App\Repositories\Contracts\SnippetRepositoryInterface;
use Illuminate\Support\Str;
use Throwable;

readonly class SnippetJsonTransferService
{
    public function __construct(
        private SnippetRepositoryInterface $repository,
        private SnippetService $snippetService
    ) {}

    public function exportToArray(User $user, iterable $snippetIds = []): array
    {
        $ids = collect($snippetIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $snippets = $ids === []
            ? $this->repository->findBy(['user_id' => $user->id])
            : $this->repository->findManyForUser($user->id, $ids, ['tags']);

        $exportData = $snippets->loadMissing('tags')
            ->map(function (Snippet $snippet): array {
                return SnippetExportItemData::fromArray([
                    'uuid' => $snippet->uuid,
                    'title' => $snippet->title,
                    'code' => $snippet->code,
                    'language' => $snippet->language,
                    'is_public' => $snippet->is_public,
                    'tags' => $snippet->tags->pluck('name')->all(),
                ])->toArray();
            });

        return SnippetArchiveEnvelopeData::fromItems($exportData)->toArray();
    }

    public function importFromArray(User $user, array $payload, ImportOptionsData $options): ImportResultData
    {
        $result = new ImportResultData();
        $items = $payload['items'] ?? null;

        if (! is_array($items)) {
            $result->addError('Invalid payload: `items` must be an array.');

            return $result;
        }

        foreach ($items as $index => $rawItem) {
            if (! is_array($rawItem)) {
                $result->addError('Invalid item payload: expected object.', $index);
                continue;
            }

            try {
                $item = SnippetExportItemData::fromArray($rawItem);
                $existing = $this->repository->findOneBy([
                    'user_id' => $user->id,
                    'uuid' => $item->uuid,
                ]);

                if ($options->dryRun) {
                    if ($existing instanceof Snippet) {
                        if ($options->onDuplicate === 'skip') {
                            $result->addSkipped();
                        } else {
                            $result->updated++;
                        }
                    } else {
                        $result->created++;
                    }
                    continue;
                }

                if ($existing instanceof Snippet) {
                    $this->handleDuplicateSnippet($existing, $item, $options, $user, $result);
                    continue;
                }

                $created = $this->snippetService->create(
                    $this->buildSnippetPayload($item, $user, $options, null)
                );
                $result->addCreated((int) $created->id);
            } catch (Throwable $e) {
                $result->addError($e->getMessage(), (int) $index, (string) ($rawItem['title'] ?? ''));
            }
        }

        return $result;
    }

    private function handleDuplicateSnippet(Snippet $existing, SnippetExportItemData $item, ImportOptionsData $options, User $user, ImportResultData $result): void
    {
        if ($options->onDuplicate === 'skip') {
            $result->addSkipped();
            return;
        }

        if ($options->onDuplicate === 'update') {
            $updated = $this->snippetService->update(
                $existing,
                $this->buildSnippetPayload($item, $user, $options, $existing->uuid)
            );
            $result->addUpdated((int) $updated->id);
            return;
        }

        $copyPayload = $this->buildSnippetPayload($item, $user, $options, null);
        $copyPayload['uuid'] = (string) \Illuminate\Support\Str::uuid();
        $copyPayload['title'] = $item->title.' (copy)';

        $copy = $this->snippetService->create($copyPayload);
        $result->addCreated((int) $copy->id);
    }

    private function buildSnippetPayload(SnippetExportItemData $item, User $user, ImportOptionsData $options, ?string $uuidOverride): array
    {
        $isPublic = $options->defaultIsPublic ?? $item->is_public;

        return [
            'uuid' => $uuidOverride ?? ($options->preserveUuid ? $item->uuid : (string) Str::uuid()),
            'user_id' => $user->id,
            'title' => $item->title,
            'code' => $item->code,
            'language' => $item->language?->value,
            'is_public' => $isPublic,
            'tags' => $item->tags->all(),
        ];
    }
}
