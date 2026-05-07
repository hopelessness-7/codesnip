<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SnippetFilterRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'language' => ['nullable', 'string', 'max:50'],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in(['title', 'created_at', 'updated_at', 'language'])
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'Поисковый запрос не может превышать 255 символов',
            'tags.array' => 'Теги должны быть массивом',
            'tags.*.string' => 'Каждый тег должен быть строкой',
            'tags.*.max' => 'Каждый тег не может превышать 50 символов',
            'language.in' => 'Указан неподдерживаемый язык программирования',
            'sort_by.in' => 'Недопустимое поле для сортировки',
            'sort_direction.in' => 'Направление сортировки может быть только asc или desc',
            'per_page.min' => 'Минимальное количество элементов на странице: 1',
            'per_page.max' => 'Максимальное количество элементов на странице: 100',
            'page.min' => 'Номер страницы должен быть положительным числом',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_by' => $this->input('sort_by', 'updated_at'),
            'sort_direction' => $this->input('sort_direction', 'desc'),
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
        ]);

        if ($this->has('query')) {
            $this->merge([
                'query' => strip_tags(trim($this->input('query')))
            ]);
        }
    }
}
