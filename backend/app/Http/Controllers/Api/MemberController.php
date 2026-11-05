<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Requests\Member\UpdateMemberStatusRequest;
use App\Http\Resources\MemberResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $members) {}

    /**
     * GET /api/members?search=&status=&source=&plan_id=&late=1&page=&per_page=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        return MemberResource::collection(
            $this->members->paginate($request->only(['search', 'status', 'source', 'plan_id', 'late']), $perPage)
        );
    }

    /**
     * POST /api/members (multipart si photo)
     */
    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = $this->members->create($request->safe()->except('photo'), $request->file('photo'));

        return (new MemberResource($member))->response()->setStatusCode(201);
    }

    /**
     * GET /api/members/{member}
     */
    public function show(Member $member): MemberResource
    {
        return new MemberResource($member->load(['currentSubscription.plan', 'lastAttendance']));
    }

    /**
     * PUT /api/members/{member}
     */
    public function update(UpdateMemberRequest $request, Member $member): MemberResource
    {
        return new MemberResource(
            $this->members->update($member, $request->safe()->except('photo'), $request->file('photo'))
        );
    }

    /**
     * DELETE /api/members/{member} [manager] - soft delete (RG-ADH-04)
     */
    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/members/{member}/status [manager]
     * Payload : { status: "suspended" | "active" | "inactive", reason? }
     */
    public function updateStatus(UpdateMemberStatusRequest $request, Member $member): MemberResource
    {
        $status = MemberStatus::from($request->status);

        if ($status === MemberStatus::Suspended && $request->filled('reason')) {
            $member->notes = trim(($member->notes ? $member->notes."\n" : '').
                'Suspendu le '.now()->format('d/m/Y').' : '.$request->reason);
            $member->save();
        }

        return new MemberResource(
            $this->members->changeStatus($member, $status, $request->user())->load(['currentSubscription.plan', 'lastAttendance'])
        );
    }

    /**
     * POST /api/members/{member}/validate (agent ou gérant)
     * Valide la demande d'adhésion sans paiement (RG-ADH-06, cas manuel).
     * Réponse 200 : MemberResource ; 422 member_suspended
     */
    public function validateMembership(Request $request, Member $member): MemberResource
    {
        return new MemberResource(
            $this->members->validateMembership($member, $request->user())->load(['currentSubscription.plan', 'lastAttendance'])
        );
    }

    /**
     * GET /api/members/{member}/subscriptions
     */
    public function subscriptions(Member $member): AnonymousResourceCollection
    {
        return SubscriptionResource::collection(
            $member->subscriptions()->with(['plan', 'payments', 'creator'])->get()
        );
    }

    /**
     * GET /api/members/by-qr/{token} - utilisé par le scan
     */
    public function byQr(string $token): MemberResource
    {
        $member = Member::where('qr_token', $token)
            ->with(['currentSubscription.plan', 'lastAttendance'])
            ->firstOrFail();

        return new MemberResource($member);
    }
}
