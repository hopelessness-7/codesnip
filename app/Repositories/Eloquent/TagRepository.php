<?php

namespace App\Repositories\Eloquent;

use App\DTOs\TagData;
use App\Models\Tag;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Tag());
    }

    public function findByName(string $name): ?Tag
    {
        return $this->query()->where('name', $name)->withCount('snippets')->first();
    }

    public function findBySlug(string $slug): ?Tag
    {
        return $this->query()->where('slug', $slug)->withCount('snippets')->first();
    }

    /**
     * @throws \Throwable
     */
    public function findOrCreate(string $name, bool $isAiGenerated = false): Tag
    {
        $slug = Str::slug($name);

        return DB::transaction(function () use ($name, $slug, $isAiGenerated) {
            $tag = $this->query()->where('slug', $slug)->first();

            if (!$tag) {
                $tag = $this->create(new TagData(
                    name: $name,
                    slug: $slug,
                    is_ai_generated: $isAiGenerated
                )->toArray());
            }

            return $tag;
        });
    }

    public function getPopular(int $limit = 20): Collection
    {
        return $this->model->popular($limit)->get();
    }

    public function searchByName(string $query, int $limit = 10): Collection
    {
        return $this->query()->whereLike('name', $query)->limit($limit)->get();
    }

    public function getTagsForSnippet(int $snippetId): Collection
    {
        return $this->query()->whereHas('snippets', function ($query) use ($snippetId) {
            $query->where('snippets.id', $snippetId);
        })->get();
    }

    public function forUserSnippets(int $userId): Collection
    {
        return $this->userSnippetsQuery($userId)->get();
    }

    public function paginateForUserSnippets(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->userSnippetsQuery($userId)->paginate($perPage);
    }

    public function topForUserSnippets(int $userId, int $limit): Collection
    {
        return $this->userSnippetsQuery($userId)->limit($limit)->get();
    }

    private function userSnippetsQuery(int $userId)
    {
        return Tag::query()
            ->whereHas('snippets', fn ($q) => $q->where('user_id', $userId))
            ->withCount(['snippets as user_snippets_count' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('user_snippets_count')
            ->orderBy('name');
    }
}
