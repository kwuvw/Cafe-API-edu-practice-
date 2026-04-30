<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RemovePositionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_menu_id' => 'required|exists:order_menus,id',
        ];
    }
}
