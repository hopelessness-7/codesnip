<?php

namespace App\Repositories\Eloquent;

use App\DTOs\SearchFilters;
use App\Models\Snippet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Tag;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\SnippetRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\DTOs\BaseDTO;

readonly class SnippetRepository extends BaseRepository implements SnippetRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Snippet());
    }

    public function updateFromDTO(Model $model, BaseDTO $data): Snippet
    {
        $this->update($model, $data->toArray());

        if ($data->tags->isNotEmpty()) {
            $this->syncTags($model, $data->tags->toArray());
        }

        return $model->fresh();
    }

    public function findByUuid(string $uuid): ?Snippet
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByUser(int $userId, SearchFilters $filters): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with('tags')
            ->when($filters->query, function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('title', 'like', "%{$filters->query}%")
                        ->orWhere('code', 'like', "%{$filters->query}%");
                });
            })
            ->when($filters->language, function ($q) use ($filters) {
                $q->where('language', $filters->language);
            })
            ->when($filters->isPublic !== null, function ($q) use ($filters) {
                $q->where('is_public', $filters->isPublic);
            })
            ->when($filters->createdFrom, function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters->createdFrom);
            })
            ->when($filters->createdTo, function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters->createdTo);
            })
            ->when($filters->updatedFrom, function ($q) use ($filters) {
                $q->whereDate('updated_at', '>=', $filters->updatedFrom);
            })
            ->when($filters->updatedTo, function ($q) use ($filters) {
                $q->whereDate('updated_at', '<=', $filters->updatedTo);
            })
            ->when($filters->tags !== [], function ($q) use ($filters) {
                foreach ($filters->tags as $tag) {
                    $q->whereHas('tags', function ($sub) use ($tag) {
                        $sub->where('name', $tag)->orWhere('slug', $tag);
                    });
                }
            });

        $this->applySort($query, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function search(string $query, ?int $userId = null): LengthAwarePaginator
    {
        $builder = Snippet::search($query)
            ->query(function ($q) use ($userId) {
                $q->with('tags')
                    ->when($userId, function ($sub) use ($userId) {
                        $sub->where(function ($q) use ($userId) {
                            $q->where('user_id', $userId)
                                ->orWhere('is_public', true);
                        });
                    }, function ($sub) {
                        $sub->where('is_public', true);
                    });
            });

        return $builder->paginate(15);
    }

    public function getByTag(string $tag, ?int $userId = null): LengthAwarePaginator
    {
        return $this->query()
            ->with('tags')
            ->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag)->orWhere('slug', $tag);
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->orWhere('is_public', true);
                });
            }, function ($q) {
                $q->where('is_public', true);
            })
            ->latest()
            ->paginate(15);
    }

    public function getPublicSnippets(SearchFilters $filters): LengthAwarePaginator
    {
        $query = $this->query()
            ->with(['user:id,name', 'tags'])
            ->where('is_public', true)
            ->when($filters->ownerId, function ($q) use ($filters) {
                $q->where('user_id', $filters->ownerId);
            })
            ->when($filters->query, function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('title', 'like', "%{$filters->query}%")
                        ->orWhere('code', 'like', "%{$filters->query}%");
                });
            })
            ->when($filters->language, function ($q) use ($filters) {
                $q->where('language', $filters->language);
            })
            ->when($filters->createdFrom, function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters->createdFrom);
            })
            ->when($filters->createdTo, function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters->createdTo);
            })
            ->when($filters->updatedFrom, function ($q) use ($filters) {
                $q->whereDate('updated_at', '>=', $filters->updatedFrom);
            })
            ->when($filters->updatedTo, function ($q) use ($filters) {
                $q->whereDate('updated_at', '<=', $filters->updatedTo);
            })
            ->when($filters->tags !== [], function ($q) use ($filters) {
                foreach ($filters->tags as $tag) {
                    $q->whereHas('tags', function ($sub) use ($tag) {
                        $sub->where('name', $tag)->orWhere('slug', $tag);
                    });
                }
            });

        $this->applySort($query, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    private function applySort($query, SearchFilters $filters): void
    {
        if ($filters->sortBy === 'relevance') {
            $search = trim($filters->query);
            if ($search !== '') {
                $query->orderByRaw(
                    "(CASE WHEN title LIKE ? THEN 2 WHEN code LIKE ? THEN 1 ELSE 0 END) DESC",
                    ["%{$search}%", "%{$search}%"]
                )->orderByDesc('updated_at');

                return;
            }
        }

        $allowed = ['updated_at', 'created_at', 'title', 'language'];
        $sortBy = in_array($filters->sortBy, $allowed, true) ? $filters->sortBy : 'updated_at';
        $query->orderBy($sortBy, $filters->sortDirection);
    }

    public function attachTags(Snippet $snippet, array $tags): void
    {
        $tagIds = [];
        $tagRepository = app(TagRepositoryInterface::class);

        foreach ($tags as $tag) {
            $tag = $tagRepository->findOrCreate($tag['name'], $tag['is_ai_generated']);
            $tagIds[] = $tag->id;
            $tag->incrementCount();
        }

        $snippet->tags()->syncWithoutDetaching($tagIds);
    }

    public function syncTags(Snippet $snippet, array $tags): void
    {
        $tagIds = [];
        $tagRepository = app(TagRepositoryInterface::class);

        // Уменьшаем счётчик у старых тегов
        foreach ($snippet->tags as $oldTag) {
            $oldTag->decrementCount();
        }

        // Добавляем новые теги
        foreach ($tags as $tagName) {
            $tag = $tagRepository->findOrCreate($tagName);
            $tagIds[] = $tag->id;
            $tag->incrementCount();
        }

        $snippet->tags()->sync($tagIds);
    }

    public function getPopularTags(?int $userId = null, int $limit = 20): Collection
    {
        return Tag::query()
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('snippets', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->orWhere('is_public', true);
                });
            }, function ($q) {
                $q->whereHas('snippets', function ($sub) {
                    $sub->where('is_public', true);
                });
            })
            ->orderByDesc('snippets_count')
            ->limit($limit)
            ->get();
    }
}
