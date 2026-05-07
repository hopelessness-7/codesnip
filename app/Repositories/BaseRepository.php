<?php

namespace App\Repositories;

use App\DTOs\BaseDTO;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract readonly class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @param Model $model
     */
    public function __construct(
        protected Model $model
    ) {}

    /**
     * Получить новый экземпляр query builder
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function findById(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function findAll(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns);
    }

    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function findBy(array $criteria): Collection
    {
        return $this->applyCriteria($criteria)->get();
    }

    public function findOneBy(array $criteria): ?Model
    {
        return $this->applyCriteria($criteria)->first();
    }

    public function count(array $criteria = []): int
    {
        return $this->applyCriteria($criteria)->count();
    }

    public function exists(array $criteria): bool
    {
        return $this->applyCriteria($criteria)->exists();
    }

    public function insert(array $data): bool
    {
        return $this->query()->insert($data);
    }

    public function updateWhere(array $criteria, array $data): int
    {
        return $this->applyCriteria($criteria)->update($data);
    }

    public function deleteWhere(array $criteria): int
    {
        return $this->applyCriteria($criteria)->delete();
    }

    /**
     * Применить критерии к запросу
     */
    protected function applyCriteria(array $criteria): Builder
    {
        $query = $this->query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                // Если передан массив из 3 элементов: [operator, value]
                if (count($value) === 2 && isset($value[0], $value[1])) {
                    $query->where($field, $value[0], $value[1]);
                } else {
                    $query->whereIn($field, $value);
                }
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    public function createFromDTO(BaseDTO $data): Model
    {
        return $this->create($data->toArray());
    }

    public function updateFromDTO(Model $model, BaseDTO $data): Model
    {
        $this->update($model, $data->toArray());
        return $model;
    }
}
