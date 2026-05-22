<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
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

        $roleName = null;

        if (isset($user->role_id)) {
            $roleName = DB::table('roles')
                ->where('id', $user->role_id)
                ->value('name');
        }

        $normalizedRole = is_string($roleName) ? strtolower(trim($roleName)) : null;
        $allowedRoles = collect($roles)
            ->map(fn (string $role) => strtolower(trim($role)))
            ->filter()
            ->values();

        if ($normalizedRole !== null && $allowedRoles->contains($normalizedRole)) {
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
