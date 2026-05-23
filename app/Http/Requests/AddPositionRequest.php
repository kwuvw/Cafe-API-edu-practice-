<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddPositionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'menu_id' => $this->input('menu_id') ?? $this->input('dish_id'),
            'count' => $this->input('count') ?? $this->input('quantity'),
        ]);
    }

    public function rules(): array
    {
        return [
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'count' => ['required', 'integer', 'min:1'],
        ];
    }
}
