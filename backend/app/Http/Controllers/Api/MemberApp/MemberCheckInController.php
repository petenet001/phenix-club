<?php

namespace App\Http\Controllers\Api\MemberApp;

use App\Enums\AttendanceMethod;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Services\KioskTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCheckInController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendances,
        private readonly KioskTokenService $kiosk,
    ) {}

    /**
     * POST /api/member/check-in { code } - code = contenu du QR affiché sur la borne.
     * Mêmes règles que le pointage à l'accueil ; un adhérent ne peut jamais forcer.
     * 201 { data: { checked_in_at, plan, ... }, warning } ; 422 invalid_checkin_code | code métier de refus
     */
    public function store(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:200']])['code'];

        if (! $this->kiosk->isValid($code)) {
            throw new BusinessRuleException('invalid_checkin_code', 'QR code expiré ou invalide : scannez l\'écran de la salle.');
        }

        $result = $this->attendances->checkIn($request->user(), AttendanceMethod::App, null);
        $attendance = $result['attendance'];

        return response()->json([
            'data' => [
                'id' => $attendance->id,
                'checked_in_at' => $attendance->checked_in_at->toIso8601String(),
                'plan' => $attendance->subscription?->plan?->name,
                'sessions_count' => $attendance->subscription?->plan?->sessions_count,
                'sessions_used' => $attendance->subscription?->sessions_used,
            ],
            'warning' => $result['warning'],
            'message' => 'Présence enregistrée, bonne séance !',
        ], 201);
    }
}
