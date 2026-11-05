<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LatePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LatePaymentController extends Controller
{
    /**
     * GET /api/late-payments?min_days=
     * Réponse : { data: [...], summary: { count, amount, rate } }
     */
    public function index(Request $request, LatePaymentService $service): JsonResponse
    {
        $list = $service->list((int) $request->integer('min_days', 0));

        return response()->json([
            'data' => $list->values(),
            'summary' => [
                'count' => $list->count(),
                'amount' => (int) $list->sum('amount_due'),
            ],
        ]);
    }
}
