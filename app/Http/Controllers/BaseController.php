<?php

namespace App\Http\Controllers;

use App\DTOs\BaseDTO;
use App\Http\Requests\BaseRequest;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    protected BaseService $service;

    protected const FORMAT_JSON = 'json';
    protected const FORMAT_HTML = 'html';
    protected const FORMAT_INERTIA = 'inertia';

    protected ?string $resourceClass = null;

    protected ?string $requestClass = null;

    protected array $additionalResponseData = [];

    public function index(BaseRequest $request): JsonResponse
    {
        try {
            $filters = $this->prepareFilters($request);
            $items = $this->service->all();

            return $this->respondWithCollection($items);
        } catch (\Exception $e) {
            return $this->respondWithError($e, __FUNCTION__);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->service->find($id);

            $this->authorizeAction('view', $item);

            return $this->respondWithItem($item);
        } catch (\Exception $e) {
            return $this->respondWithError($e, __FUNCTION__);
        }
    }

    /**
     * Создать новую запись.
     */
    public function store(BaseRequest $request): JsonResponse
    {
        try {
            $this->authorizeAction('create');
            $validatedRequest = app($this->requestClass);
            $data = $this->prepareData($validatedRequest);
            $item = $this->service->create($data->toArray());

            return $this->respondWithItem($item, 201);
        } catch (\Exception $e) {
            return $this->respondWithError($e, __FUNCTION__);
        }
    }

    /**
     * Обновить запись.
     */
    public function update(BaseRequest $request, Model $model): JsonResponse
    {
        try {
            $item = $this->service->find($model->id);

            $this->authorizeAction('update', $item);
            $validatedRequest = app($this->requestClass);
            $data = $this->prepareData($validatedRequest);
            $updated = $this->service->update($model, $data->toArray());

            return $this->respondWithItem($updated);
        } catch (\Exception $e) {
            return $this->respondWithError($e, __FUNCTION__);
        }
    }

    /**
     * Удалить запись.
     */
    public function destroy(Model $model): JsonResponse
    {
        try {
            $item = $this->service->find($model->id);

            $this->authorizeAction('delete', $item);

            $this->service->delete($model);

            return $this->respondWithSuccess(
                message: 'Record deleted successfully',
                code: 200
            );
        } catch (\Exception $e) {
            return $this->respondWithError($e, __FUNCTION__);
        }
    }

    /**
     * Подготовить фильтры из запроса.
     */
    protected function prepareFilters(BaseRequest $request): array
    {
        return $request->only([
            'query',
            'sort_by',
            'sort_direction',
            'per_page',
            'page',
        ]);
    }

    /**
     * Подготовить данные из запроса.
     */
    protected function prepareData(BaseRequest $request): BaseDTO
    {
        return $request->fromDTO();
    }

    /**
     * Проверить права доступа.
     */
    protected function authorizeAction(string $action, $model = null): void
    {
        if (method_exists($this, 'authorize')) {
            if ($model) {
                $this->authorize($action, $model);
            } else {
                $this->authorize($action);
            }
        }
    }

    /**
     * Успешный ответ с коллекцией.
     */
    protected function respondWithCollection($items, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $this->transformCollection($items),
            'meta' => $this->getMetadata($items),
        ];

        return response()->json(
            array_merge($response, $this->additionalResponseData),
            $code
        );
    }

    /**
     * Успешный ответ с одним элементом.
     */
    protected function respondWithItem($item, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $this->transformItem($item),
        ];

        return response()->json(
            array_merge($response, $this->additionalResponseData),
            $code
        );
    }

    /**
     * Успешный ответ без данных.
     */
    protected function respondWithSuccess(
        array $data = [],
        string $message = 'Operation successful',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Ответ с ошибкой.
     */
    protected function respondWithError(\Exception $e, string $context = ''): JsonResponse
    {
        $code = $this->getErrorCode($e);
        $message = $this->getErrorMessage($e);

        \Log::error("BaseController error in {$context}", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $code,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], $code);
    }

    /**
     * Трансформировать коллекцию через API Resource.
     */
    protected function transformCollection($items)
    {
        if ($this->resourceClass && class_exists($this->resourceClass)) {
            return $this->resourceClass::collection($items);
        }

        return $items;
    }

    /**
     * Трансформировать элемент через API Resource.
     */
    protected function transformItem($item)
    {
        if ($this->resourceClass && class_exists($this->resourceClass)) {
            return new $this->resourceClass($item);
        }

        return $item;
    }

    /**
     * Получить метаданные пагинации.
     */
    protected function getMetadata($items): array
    {
        if (method_exists($items, 'total')) {
            return [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ];
        }

        return [];
    }

    /**
     * Получить HTTP код ошибки.
     */
    protected function getErrorCode(\Exception $e): int
    {
        return match (true) {
            $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
            $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
            $e instanceof \InvalidArgumentException => 422,
            $e instanceof \Illuminate\Validation\ValidationException => 422,
            default => 500,
        };
    }

    /**
     * Получить сообщение ошибки.
     */
    protected function getErrorMessage(\Exception $e): string
    {
        return match (true) {
            $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 'Record not found',
            $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'Access denied',
            default => $e->getMessage(),
        };
    }
}
