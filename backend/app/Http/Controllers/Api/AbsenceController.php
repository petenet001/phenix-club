<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AbsenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    /**
     * GET /api/absences?days= (défaut paramètre inactivity_days)
     */
    public function index(Request $request, AbsenceService $service): JsonResponse
    {
        $days = $request->filled('days') ? (int) $request->days : Setting::inactivityDays();
        $list = $service->list($days);

        return response()->json(['data' => $list->values(), 'summary' => ['count' => $list->count(), 'days' => $days]]);
    }
}
