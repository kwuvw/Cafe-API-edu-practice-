<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserAddRequest;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function login(UserLoginRequest $request)
    {
        $credentials = $request->only('login', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Authentication failed'
                ]
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();


        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['data' => ['token' => $token]]);
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
        return UserResource::collection($users);
    }

    public function store(UserAddRequest $request)
    {

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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход'
        ]);
    }
}
