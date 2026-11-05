<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route au personnel : un token d'adhérent (application mobile) est refusé.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            return response()->json(['error' => 'forbidden', 'message' => 'Accès réservé au personnel.'], 403);
        }

        return $next($request);
    }
}
