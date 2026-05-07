<?php

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class BaseDTO
{
    /**
     * Создать DTO из массива данных (каждый наследник реализует сам)
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Создать DTO из HTTP запроса
     */
    public static function fromRequest(Request $request): static
    {
        return static::fromArray($request->all());
    }

    /**
     * Преобразовать DTO в массив
     */
    abstract public function toArray(): array;

    /**
     * Преобразовать DTO в коллекцию (удобно для фильтрации)
     */
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }

    /**
     * Получить только заполненные поля (не null)
     */
    public function toArrayFilled(): array
    {
        return array_filter($this->toArray(), fn($value) => !is_null($value));
    }

    /**
     * Преобразовать в JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
