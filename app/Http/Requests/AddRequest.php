<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:tables,id',
            'number_of_person' => 'required|integer|min:1', // Твое исправленное поле
        ];
    }
}
