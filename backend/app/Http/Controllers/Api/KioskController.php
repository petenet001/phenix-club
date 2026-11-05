<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\KioskTokenService;
use App\Services\MemberAppService;
use Illuminate\Http\JsonResponse;

class KioskController extends Controller
{
    public function __construct(private readonly KioskTokenService $kiosk, private readonly MemberAppService $app) {}

    /** GET /api/kiosk/token - QR de pointage à afficher sur la borne (renouvelé toutes les 30 s) */
    public function token(): JsonResponse
    {
        return response()->json($this->kiosk->current());
    }

    /** POST /api/members/{member}/app-access - génère un code d'accès (affiché une seule fois) */
    public function grantAccess(Member $member): JsonResponse
    {
        $code = $this->app->grantAccess($member);

        return response()->json([
            'code' => $code,
            'phone' => $member->phone,
            'message' => "Bonjour {$member->first_name}, votre accès à l'application Phenix Club : téléphone {$member->phone}, code {$code}. Vous pourrez le changer dans l'application.",
        ], 201);
    }

    /** DELETE /api/members/{member}/app-access - retire l'accès et déconnecte ses appareils */
    public function revokeAccess(Member $member): JsonResponse
    {
        $this->app->revokeAccess($member);

        return response()->json(null, 204);
    }
}
