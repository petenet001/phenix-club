<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reminder\StoreReminderRequest;
use App\Http\Resources\ReminderResource;
use App\Models\Member;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReminderController extends Controller
{
    public function __construct(private readonly ReminderService $reminders) {}

    /**
     * POST /api/reminders
     * Payload : { member_id, type, channel, message?, override? }
     * 201 ReminderResource (+ link wa.me / sms: quand l'envoi est manuel) ; 422 reminder_too_recent | phone_missing ; 403 override_requires_manager
     */
    public function store(StoreReminderRequest $request): JsonResponse
    {
        $member = Member::findOrFail($request->member_id);

        $reminder = $this->reminders->send(
            $member,
            ReminderType::from($request->type),
            ReminderChannel::from($request->channel),
            $request->input('message'),
            $request->user(),
            $request->boolean('override')
        );

        return (new ReminderResource($reminder))->response()->setStatusCode(201);
    }

    /**
     * GET /api/reminders/preview?member_id=&type= - message par défaut à éditer avant envoi
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate(['member_id' => ['required', 'integer'], 'type' => ['required']]);
        $member = Member::findOrFail($request->member_id);

        return response()->json(['message' => $this->reminders->defaultMessage($member, ReminderType::from($request->type))]);
    }

    /**
     * GET /api/members/{member}/reminders
     */
    public function byMember(Member $member): AnonymousResourceCollection
    {
        return ReminderResource::collection($member->reminders()->with('sender')->limit(50)->get());
    }
}
