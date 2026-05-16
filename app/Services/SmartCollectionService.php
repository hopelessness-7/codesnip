<?php

namespace App\Services;

use App\DTOs\SmartCollectionRulesData;
use App\Models\SmartCollection;
use App\Models\Snippet;
use App\Repositories\Eloquent\SmartCollectionRepository;
use App\Repositories\Eloquent\SnippetRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmartCollectionService extends BaseService
{
    public function __construct(
        SmartCollectionRepository $repository,
         private readonly SnippetRepository $snippetRepository
    ) {
        $this->repository = $repository;
    }

    public function listForUser(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }

    public function paginateForUser(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = $this->repository->paginateByUser($userId, $perPage);

        $paginator->getCollection()->transform(function (SmartCollection $collection): SmartCollection {
            $collection->rules_summary = $this->summarizeRules($collection->filters_json ?? []);

            return $collection;
        });

        return $paginator;
    }

    public function summarizeRules(array $filters): string
    {
        $rules = SmartCollectionRulesData::fromArray($filters);
        $parts = [];

        if ($rules->language !== null) {
            $parts[] = __('smart_collections.rule_language', [
                'language' => __('languages.'.$rules->language->value),
            ]);
        }

        if ($rules->is_public === true) {
            $parts[] = __('smart_collections.rule_visibility_public');
        } elseif ($rules->is_public === false) {
            $parts[] = __('smart_collections.rule_visibility_private');
        }

        if ($rules->tags !== []) {
            $parts[] = __('smart_collections.rule_tags', [
                'tags' => implode(', ', $rules->tags),
                'mode' => $rules->tags_mode === 'any'
                    ? __('smart_collections.tags_mode_any')
                    : __('smart_collections.tags_mode_all'),
            ]);
        }

        if ($rules->query !== '') {
            $parts[] = __('smart_collections.rule_query', ['query' => $rules->query]);
        }

        if ($rules->created_from || $rules->created_to || $rules->updated_from || $rules->updated_to) {
            $parts[] = __('smart_collections.rule_dates');
        }

        return $parts === []
            ? __('smart_collections.rules_none')
            : implode(' · ', $parts);
    }

    public function findForUser(int $userId, int $collectionId): ?SmartCollection
    {
        return $this->repository->findForUser($userId, $collectionId);
    }

    public function matchesSnippetByRules(Snippet $snippet, SmartCollectionRulesData $rules): bool
    {
        if ($rules->language !== null && $snippet->language !== $rules->language->value) {
            return false;
        }

        if ($rules->is_public !== null && (bool) $snippet->is_public !== $rules->is_public) {
            return false;
        }

        if ($rules->query !== '') {
            $q = mb_strtolower($rules->query);
            $inTitle = str_contains(mb_strtolower((string) $snippet->title), $q);
            $inCode = str_contains(mb_strtolower((string) $snippet->code), $q);

            if (! $inTitle && ! $inCode) {
                return false;
            }
        }

        if ($rules->created_from && Carbon::parse($snippet->created_at)->lt(Carbon::parse($rules->created_from)->startOfDay())) {
            return false;
        }

        if ($rules->created_to && Carbon::parse($snippet->created_at)->gt(Carbon::parse($rules->created_to)->endOfDay())) {
            return false;
        }

        if ($rules->updated_from && Carbon::parse($snippet->updated_at)->lt(Carbon::parse($rules->updated_from)->startOfDay())) {
            return false;
        }

        if ($rules->updated_to && Carbon::parse($snippet->updated_at)->gt(Carbon::parse($rules->updated_to)->endOfDay())) {
            return false;
        }

        if ($rules->tags !== []) {
            $snippetTags = $snippet->tags
                ->map(fn ($tag) => mb_strtolower(trim((string) ($tag->slug ?: $tag->name))))
                ->filter()
                ->values()
                ->all();
            if ($rules->tags_mode === 'any') {
                $hasAny = collect($rules->tags)->contains(fn ($tag) => in_array($tag, $snippetTags, true));
                if (! $hasAny) {
                    return false;
                }
            } else { // all
                if (array_any($rules->tags, fn($tag) => !in_array($tag, $snippetTags, true))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function refreshMembershipForSnippet(int $snippetId): void
    {
        DB::transaction(function () use ($snippetId) {
           /** @var Snippet $snippet */
           $snippet = $this->snippetRepository->findOrFail($snippetId);

           $collections = $this->repository->findByUser($snippet->user_id);

           foreach ($collections as $collection) {
               $rules = SmartCollectionRulesData::fromArray($collection->filters_json ?? []);
               $matches = $this->matchesSnippetByRules($snippet, $rules);

               if ($matches) {
                   $collection->snippets()->syncWithoutDetaching([
                       $snippet->id => ['matched_at' => now()],
                   ]);
               } else {
                   $collection->snippets()->detach($snippet->id);
               }
           }
        });
    }

    /**
     * @throws \Throwable
     */
    public function rebuildMembershipForCollection(int $collectionId): void
    {
        DB::transaction(function () use ($collectionId) {
            /** @var \App\Models\SmartCollection $collection */
            $collection = $this->repository->findOrFail($collectionId);
            $rules = SmartCollectionRulesData::fromArray($collection->filters_json ?? []);
            $snippets = $this->snippetRepository->getByUserSnippets($collection->user_id);

            $collection->snippets()->detach();

            $attachPayload = [];
            $now = now();

            foreach ($snippets as $snippet) {
                if ($this->matchesSnippetByRules($snippet, $rules)) {
                    $attachPayload[$snippet->id] = ['matched_at' => $now];
                }
            }

            if ($attachPayload !== []) {
                $collection->snippets()->attach($attachPayload);
            }
        });
    }
}
