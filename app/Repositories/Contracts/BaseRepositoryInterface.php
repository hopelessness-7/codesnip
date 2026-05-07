<?php

namespace App\Repositories\Contracts;

use App\DTOs\BaseDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Найти запись по ID
     */
    public function findById(int $id): ?Model;

    /**
     * Найти запись по ID или выбросить исключение
     */
    public function findOrFail(int $id): Model;

    /**
     * Получить все записи
     */
    public function all(): Collection;

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Создать новую запись
     */
    public function create(array $data): Model;

    /**
     * Обновить существующую запись
     */
    public function update(Model $model, array $data): bool;

    /**
     * Удалить запись
     */
    public function delete(Model $model): bool;

    /**
     * Найти записи по критериям
     */
    public function findBy(array $criteria): Collection;

    /**
     * Найти одну запись по критериям
     */
    public function findOneBy(array $criteria): ?Model;

    /**
     * Получить количество записей
     */
    public function count(array $criteria = []): int;

    /**
     * Проверить существование записи
     */
    public function exists(array $criteria): bool;

    /**
     * Массовое создание
     */
    public function insert(array $data): bool;

    /**
     * Массовое обновление
     */
    public function updateWhere(array $criteria, array $data): int;

    /**
     * Массовое удаление
     */
    public function deleteWhere(array $criteria): int;

    public function createFromDTO (BaseDTO $data): Model;
    public function updateFromDTO (Model $model, BaseDTO $data): Model;
}
