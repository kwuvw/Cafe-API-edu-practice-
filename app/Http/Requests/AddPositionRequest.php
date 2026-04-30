<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddPositionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'menu_id' => 'required|exists:menus,id',
            'count' => 'required|integer|min:1',
        ];
    }
}
