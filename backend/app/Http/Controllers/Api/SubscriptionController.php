<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * POST /api/subscriptions
     * Payload : { member_id, subscription_plan_id, start_date? (Y-m-d) }
     * Réponse 201 : SubscriptionResource ; 422 subscription_overlap | pending_already_exists | member_suspended | plan_inactive
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $member = Member::findOrFail($request->member_id);
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
        $startDate = $request->filled('start_date') ? Carbon::createFromFormat('Y-m-d', $request->start_date) : null;

        $subscription = $this->subscriptions->subscribe($member, $plan, $startDate, $request->user());

        return (new SubscriptionResource($subscription))->response()->setStatusCode(201);
    }

    /**
     * GET /api/subscriptions/{subscription}
     */
    public function show(Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($subscription->load(['plan', 'payments', 'creator']));
    }

    /**
     * GET /api/members/{member}/subscriptions/suggested-start
     * Réponse : { suggested_start_date: "2026-12-01" }
     */
    public function suggestedStart(Member $member): JsonResponse
    {
        return response()->json([
            'suggested_start_date' => $this->subscriptions->suggestedStartDate($member)->toDateString(),
        ]);
    }

    /**
     * PATCH /api/subscriptions/{subscription}/cancel [manager]
     * Payload : { reason }
     */
    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($this->subscriptions->cancel($subscription, $request->reason));
    }
}
