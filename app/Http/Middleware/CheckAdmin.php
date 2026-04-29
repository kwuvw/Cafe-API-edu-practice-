<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->role_id == 1) {
            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => 403,
                'message' => 'Forbidden for you'
            ]
        ], 403);
    }
}
