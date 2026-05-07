<?php

namespace App\Services;

use App\DTOs\BaseDTO;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected $repository;

    public function all(array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->repository->findAll($filters);
    }

    public function find(int $id): ?Model
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Model
    {
        $model = $this->repository->create($data);
        $this->afterCreate($model, $data);

        return $model;
    }

    /**
     * @throws \Throwable
     */
    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $this->beforeUpdate($model, $data);
            $this->repository->update($model, $data);
            $this->afterUpdate($model, $data);
            return $model;
        });
    }

    public function delete(Model $model): bool
    {
        return $this->repository->delete($model);
    }

    /**
     * Хук после создания (для переопределения в дочерних классах).
     */
    protected function afterCreate(Model $model, array $data): void
    {
        //
    }

    /**
     * Хук перед обновлением (для переопределения в дочерних классах).
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        //
    }

    /**
     * Хук после обновления (для переопределения в дочерних классах).
     */
    protected function afterUpdate(Model $model, array $data): void
    {
        //
    }
}
