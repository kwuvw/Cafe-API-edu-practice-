<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $user = User::where('login', trim($request->login))->first();

        if (!$user || $request->password !== $user->password) {
            return response()->json([
                'message' => 'Неверный логин или пароль'
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;


        return response()->json([
            'token' => $token,
            'role' => $user->role_id
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход'
        ]);
    }

    public function index(Request $request)
    {
        if ($request->user()->role_id != 1) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Forbidden for you'
                ]
            ], 403);
        }

        $users = User::all();
        return response()->json([
            'data' => $users
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'patronymic' => 'nullable|string',
            'login' => 'required|string|unique:users,login',
            'password' => 'required|string',
            'role_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'patronymic' => $request->patronymic,
            'login' => $request->login,
            'password' => $request->password,
            'role_id' => $request->role_id,
            'status' => 'working',
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'message' => 'Сотрудник успешно добавлен'
            ]
        ], 201);
    }
}
