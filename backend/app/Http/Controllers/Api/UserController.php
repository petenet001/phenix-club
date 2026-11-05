<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::orderBy('name')->get());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(StoreUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($user->id === $request->user()->id && (($data['role'] ?? 'manager') !== 'manager' || ! ($data['is_active'] ?? true))) {
            throw new BusinessRuleException('cannot_demote_self', 'Vous ne pouvez pas retirer vos propres droits de gérant.');
        }

        $user->update($data);

        return new UserResource($user->refresh());
    }

    /** Désactivation plutôt que suppression (historique conservé). */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            throw new BusinessRuleException('cannot_deactivate_self', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return response()->json(null, 204);
    }
}
