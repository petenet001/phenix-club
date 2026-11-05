<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MemberAppService;
use Illuminate\Http\JsonResponse;

/**
 * Données publiques du site vitrine (sans authentification) : coordonnées, formules actives,
 * frais d'inscription et ouverture des inscriptions en ligne. Aucune donnée d'adhérent.
 */
class PublicSiteController extends Controller
{
    public function __construct(private readonly MemberAppService $app) {}

    /** GET /api/public/club */
    public function show(): JsonResponse
    {
        return response()->json([
            'club' => $this->app->club(),
            'plans' => $this->app->plans(),
            'registration_fee' => (int) Setting::get('registration_fee', 0),
            'online_registration_enabled' => (bool) Setting::get('online_registration_enabled', false),
        ]);
    }
}
