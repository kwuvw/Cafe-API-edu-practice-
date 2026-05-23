<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(UserLoginRequest $request)
    {
        $credentials = $request->only('login', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Authentication failed',
                ],
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'role' => $user->role_code,
                'role_id' => (int) $user->role_id,
                'name' => $user->display_name,
                'redirect_to' => $user->dashboard_route,
                'active_shift_id' => WorkShift::query()->where('active', 1)->value('id'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->display_name,
                    'login' => $user->login,
                    'role' => $user->role_code,
                    'role_id' => (int) $user->role_id,
                    'status' => $user->status,
                ],
            ],
        ]);
    }

    public function index()
    {
        $users = User::query()
            ->with('role')
            ->withExists([
                'shiftWorkers as is_working' => function ($query) {
                    $query->whereHas('workShift', function ($shiftQuery) {
                        $shiftQuery->where('active', true);
                    });
                },
            ])
            ->get();

        return UserResource::collection($users);
    }

    public function store(UserAddRequest $request)
    {
        [$surname, $name, $patronymic] = $this->resolveNameParts($request);

        $user = User::create([
            'name' => $name,
            'surname' => $surname,
            'patronymic' => $patronymic,
            'login' => $request->login,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'status' => User::STATUS_WORKING,
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'message' => 'Сотрудник успешно добавлен',
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Успешный выход',
        ]);
    }

    private function resolveNameParts(UserAddRequest $request): array
    {
        if ($request->filled('surname')) {
            return [
                trim((string) $request->surname),
                trim((string) $request->name),
                $request->filled('patronymic') ? trim((string) $request->patronymic) : null,
            ];
        }

        $parts = preg_split('/\s+/u', trim((string) $request->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $surname = $parts[0] ?? trim((string) $request->name);
        $name = $parts[1] ?? $surname;
        $patronymic = $parts[2] ?? null;

        return [$surname, $name, $patronymic];
    }
}
