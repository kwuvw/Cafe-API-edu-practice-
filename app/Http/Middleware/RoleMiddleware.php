<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private const ROLE_MAP = [
        'admin' => 1,
        'waiter' => 2,
        'cook' => 3,
    ];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthenticated',
                ],
            ], 401);
        }

        $allowedRoleIds = collect($roles)
            ->flatMap(fn (string $role) => explode(',', $role))
            ->map(fn (string $role) => strtolower(trim($role)))
            ->filter()
            ->map(fn (string $role) => self::ROLE_MAP[$role] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($allowedRoleIds->isEmpty()) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Forbidden for you',
                ],
            ], 403);
        }

        if ($allowedRoleIds->contains((int) $user->role_id)) {
            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => 403,
                'message' => 'Forbidden for you',
            ],
        ], 403);
    }
}
