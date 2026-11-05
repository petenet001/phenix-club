<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard?month=YYYY-MM [manager]
     */
    public function index(Request $request, DashboardService $dashboard): JsonResponse
    {
        $month = $request->filled('month') ? Carbon::createFromFormat('Y-m', $request->month) : now();

        return response()->json($dashboard->build($month));
    }
}
