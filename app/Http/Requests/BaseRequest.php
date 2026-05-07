<?php

namespace App\Http\Requests;

use App\DTOs\BaseDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function fromDTO(): BaseDTO
    {
        return BaseDTO::fromRequest($this);
    }
}
