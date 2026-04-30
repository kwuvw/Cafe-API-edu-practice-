<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class APIRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
