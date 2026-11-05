<?php

namespace App\Http\Controllers\Api\MemberApp;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\MemberAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberAuthController extends Controller
{
    public function __construct(private readonly MemberAppService $app) {}

    /**
     * POST /api/member/login { phone, password, device_name? } -> { token, member }
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $member = Member::query()->wherePhone($data['phone'])->whereNotNull('app_password')->first();

        if ($member === null || ! Hash::check($data['password'], $member->app_password)) {
            throw ValidationException::withMessages(['phone' => ['Téléphone ou code d\'accès incorrect.']]);
        }

        $member->forceFill(['app_last_login_at' => now()])->save();

        return response()->json([
            'token' => $member->createToken(MemberAppService::deviceName($data['device_name'] ?? null))->plainTextToken,
            'member' => $this->app->profile($member),
        ]);
    }

    /** POST /api/member/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * PUT /api/member/password { current_password, password, password_confirmation }
     * Les autres appareils sont déconnectés.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $member = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $member->app_password)) {
            throw ValidationException::withMessages(['current_password' => ['Code actuel incorrect.']]);
        }

        $member->forceFill(['app_password' => $data['password']])->save();
        $member->tokens()->where('id', '<>', $member->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Code d\'accès modifié.']);
    }
}
