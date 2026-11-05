<?php

namespace App\Http\Controllers\Api\MemberApp;

use App\Enums\TrainingProgramStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MemberApp\MemberAttendanceResource;
use App\Http\Resources\MemberApp\MemberPaymentResource;
use App\Http\Resources\MemberApp\MemberProgramResource;
use App\Http\Resources\ReceiptResource;
use App\Models\Payment;
use App\Services\MemberAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consultation par l'adhérent de ses propres données. Chaque requête est limitée
 * à $request->user() : un adhérent ne voit jamais les données d'un autre.
 */
class MemberPortalController extends Controller
{
    public function __construct(private readonly MemberAppService $app) {}

    /** GET /api/member/me - écran d'accueil */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->app->summary($request->user())]);
    }

    /** GET /api/member/balance - ce qui reste à payer et retard éventuel */
    public function balance(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->app->balance($request->user())]);
    }

    /** GET /api/member/subscriptions */
    public function subscriptions(Request $request): JsonResponse
    {
        $subs = $request->user()->subscriptions()->notCancelled()->with(['plan', 'payments'])->get();

        return response()->json(['data' => $subs->map(fn ($s) => $this->app->subscription($s))->values()]);
    }

    /** GET /api/member/payments?page= */
    public function payments(Request $request): AnonymousResourceCollection
    {
        $page = $request->user()->payments()->with('subscription.plan')->paginate(min((int) $request->integer('per_page', 20), 100));

        return MemberPaymentResource::collection($page);
    }

    /** GET /api/member/payments/{payment}/receipt - 404 si le paiement n'est pas le sien */
    public function receipt(Request $request, int $payment): ReceiptResource
    {
        $model = Payment::query()->where('member_id', $request->user()->id)->findOrFail($payment);

        return new ReceiptResource($model->load(['member', 'subscription.plan', 'receiver']));
    }

    /** GET /api/member/attendances?page= */
    public function attendances(Request $request): AnonymousResourceCollection
    {
        $page = $request->user()->attendances()->with('subscription.plan')->paginate(min((int) $request->integer('per_page', 30), 100));

        return MemberAttendanceResource::collection($page);
    }

    /** GET /api/member/programs - programmes en vigueur et propositions en cours */
    public function programs(Request $request): AnonymousResourceCollection
    {
        $programs = $request->user()->trainingPrograms()
            ->whereIn('status', [TrainingProgramStatus::Imposed->value, TrainingProgramStatus::Approved->value, TrainingProgramStatus::Proposed->value])
            ->with('author')->get();

        return MemberProgramResource::collection($programs);
    }

    /** GET /api/member/plans - tarifs */
    public function plans(): JsonResponse
    {
        return response()->json(['data' => $this->app->plans(), 'club' => $this->app->club()]);
    }
}
