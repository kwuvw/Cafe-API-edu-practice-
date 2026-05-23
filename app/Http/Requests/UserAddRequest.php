<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'surname' => 'nullable|string',
            'patronymic' => 'nullable|string',
            'login' => 'required|string|unique:users,login',
            'password' => 'required|string',
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }
}
