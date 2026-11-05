<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPlan\StoreSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    /**
     * GET /api/subscription-plans?active=1
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SubscriptionPlan::query()->ordered()->withCount('subscriptions');

        if ($request->boolean('active')) {
            $query->active();
        }

        return SubscriptionPlanResource::collection($query->get());
    }

    /**
     * POST /api/subscription-plans [manager]
     */
    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return (new SubscriptionPlanResource($plan))->response()->setStatusCode(201);
    }

    /**
     * GET /api/subscription-plans/{subscription_plan}
     */
    public function show(SubscriptionPlan $subscriptionPlan): SubscriptionPlanResource
    {
        return new SubscriptionPlanResource($subscriptionPlan->loadCount('subscriptions'));
    }

    /**
     * PUT /api/subscription-plans/{subscription_plan} [manager]
     * Le changement de prix ne touche pas les abonnements existants (RG-ABO-02).
     */
    public function update(StoreSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): SubscriptionPlanResource
    {
        $subscriptionPlan->update($request->validated());

        return new SubscriptionPlanResource($subscriptionPlan->refresh()->loadCount('subscriptions'));
    }

    /**
     * DELETE /api/subscription-plans/{subscription_plan} [manager]
     * 204 si aucun abonnement lié, sinon 422 plan_in_use (désactiver à la place).
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        if ($subscriptionPlan->subscriptions()->exists()) {
            throw new BusinessRuleException('plan_in_use', 'Cette formule a déjà des abonnements, désactivez-la plutôt.');
        }

        $subscriptionPlan->delete();

        return response()->json(null, 204);
    }
}
