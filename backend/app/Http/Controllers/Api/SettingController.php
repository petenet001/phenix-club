<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * GET /api/settings - valeurs castées + libellés
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Setting::all_cast(),
            'labels' => Setting::query()->pluck('label', 'key'),
        ]);
    }

    /**
     * PUT /api/settings [manager] - { clé: valeur, ... }
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json(['data' => Setting::all_cast()]);
    }
}
