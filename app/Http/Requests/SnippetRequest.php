<?php

namespace App\Http\Requests;

use App\Enums\SnippetLanguage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SnippetRequest extends BaseRequest
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
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'code' => ['required', 'string'],
            'language' => ['required', Rule::enum(SnippetLanguage::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'is_public' => ['boolean', 'nullable'],
            'uuid' => ['nullable', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
