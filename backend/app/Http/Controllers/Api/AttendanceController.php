<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\MemberResource;
use App\Models\Attendance;
use App\Models\Member;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendances) {}

    /**
     * POST /api/attendances
     * Payload : { member_id } ou { qr_token }, method?: manual|qr, force?: bool, force_reason?
     * Réponse 201 : { data: AttendanceResource, warning: string|null }
     * 422 : membership_pending | member_suspended | already_checked_in_today | no_valid_subscription |
     *       subscription_expired | subscription_not_started | sessions_exhausted | force_reason_required
     * 403 : force_requires_manager
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $member = $request->filled('qr_token')
            ? Member::where('qr_token', $request->qr_token)->firstOrFail()
            : Member::findOrFail($request->member_id);

        $method = AttendanceMethod::from($request->input('method', $request->filled('qr_token') ? 'qr' : 'manual'));

        $result = $this->attendances->checkIn(
            $member,
            $method,
            $request->user(),
            $request->boolean('force'),
            $request->input('force_reason')
        );

        return response()->json([
            'data' => new AttendanceResource($result['attendance']),
            'warning' => $result['warning'],
        ], 201);
    }

    /**
     * GET /api/attendances/check?member_id= ou ?qr_token=
     * Prévisualisation sans enregistrer : { allowed, error, message, warning, member }
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => ['required_without:qr_token', 'nullable', 'integer'],
            'qr_token' => ['required_without:member_id', 'nullable', 'uuid'],
        ]);

        $member = $request->filled('qr_token')
            ? Member::where('qr_token', $request->qr_token)->with(['currentSubscription.plan', 'lastAttendance'])->firstOrFail()
            : Member::with(['currentSubscription.plan', 'lastAttendance'])->findOrFail($request->member_id);

        $result = $this->attendances->check($member);

        return response()->json([
            'allowed' => $result['allowed'],
            'error' => $result['error'],
            'message' => $result['message'],
            'warning' => $result['warning'],
            'member' => new MemberResource($member),
        ]);
    }

    /**
     * GET /api/attendances?date=YYYY-MM-DD (défaut aujourd'hui) | ?from=&to= | ?member_id= | ?forced=1
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::query()->with(['member', 'subscription.plan', 'recorder'])->orderByDesc('checked_in_at');

        if ($request->filled('from') && $request->filled('to')) {
            $query->between($request->from, $request->to);
        } else {
            $query->onDate($request->input('date', now()->toDateString()));
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->boolean('forced')) {
            $query->where('forced', true);
        }

        return AttendanceResource::collection($query->paginate(min((int) $request->integer('per_page', 50), 200))->withQueryString());
    }

    /**
     * GET /api/members/{member}/attendances?from=&to=
     */
    public function byMember(Request $request, Member $member): AnonymousResourceCollection
    {
        $query = $member->attendances()->with(['subscription.plan', 'recorder']);

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('attended_on', [$request->from, $request->to]);
        }

        return AttendanceResource::collection($query->paginate(min((int) $request->integer('per_page', 30), 200))->withQueryString());
    }

    /**
     * DELETE /api/attendances/{attendance} [manager] - correction d'une erreur de pointage
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        if (! $attendance->forced && $attendance->subscription && $attendance->subscription->plan?->sessions_count !== null && $attendance->subscription->sessions_used > 0) {
            $attendance->subscription->decrement('sessions_used');
        }

        $attendance->delete();

        return response()->json(null, 204);
    }
}
