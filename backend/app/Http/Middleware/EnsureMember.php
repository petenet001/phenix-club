<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route à l'application adhérent : un token du personnel est refusé.
 */
class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return response()->json(['error' => 'forbidden', 'message' => 'Accès réservé aux adhérents.'], 403);
        }

        // Accès retiré par l'accueil, ou fiche supprimée : le token n'est plus valable
        if (! $member->hasAppAccess()) {
            $member->currentAccessToken()?->delete();

            return response()->json(['error' => 'unauthenticated', 'message' => 'Accès à l\'application désactivé.'], 401);
        }

        return $next($request);
    }
}
