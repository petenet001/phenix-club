<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage : ->middleware('role:manager') ou ->middleware('role:manager,agent')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role->value, $roles, true)) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'Action réservée au rôle : '.implode(', ', $roles).'.',
            ], 403);
        }

        return $next($request);
    }
}
