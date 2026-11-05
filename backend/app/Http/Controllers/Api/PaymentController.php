<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CancelPaymentRequest;
use App\Http\Requests\Payment\StorePaymentBatchRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ReceiptResource;
use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments, private readonly ReceiptService $receipts) {}

    /**
     * GET /api/payments?date=|from=&to=|method=|type=|member_id=|search=|cancelled=0|page=
     * Réponse : liste paginée + meta.totals { total, count, by_method, by_type }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'from', 'to', 'method', 'type', 'member_id', 'search', 'cancelled']);
        if (empty($filters['date']) && empty($filters['from'])) {
            $filters['date'] = now()->toDateString();
        }

        $paginator = $this->payments->paginate($filters, min((int) $request->integer('per_page', 30), 200));

        return PaymentResource::collection($paginator)
            ->additional(['meta' => ['totals' => $this->payments->totals($filters)]])
            ->response();
    }

    /**
     * GET /api/members/{member}/outstanding - ce qu'il reste à encaisser
     */
    public function outstanding(Member $member): JsonResponse
    {
        return response()->json($this->payments->outstanding($member));
    }

    /**
     * POST /api/payments
     * Payload : { member_id, type?, subscription_id?, amount, method, reference?, paid_at?, period_start?, period_end?, notes? }
     * 201 PaymentResource ; 422 amount_exceeds_balance | reference_required | subscription_required | registration_already_paid | ...
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $member = Member::findOrFail($request->member_id);
        $payment = $this->payments->record($member, $request->validated(), $request->user());

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    /**
     * POST /api/payments/batch - plusieurs lignes (frais d'inscription + abonnement) en un encaissement
     * Payload : { member_id, method, reference?, paid_at?, notes?, lines: [ { type, amount, subscription_id? } ] }
     * 201 { data: [PaymentResource] }
     */
    public function storeBatch(StorePaymentBatchRequest $request): JsonResponse
    {
        $member = Member::findOrFail($request->member_id);
        $payments = $this->payments->recordBatch(
            $member,
            $request->validated('lines'),
            $request->safe()->only(['method', 'reference', 'paid_at', 'notes']),
            $request->user()
        );

        return PaymentResource::collection(collect($payments))->response()->setStatusCode(201);
    }

    /**
     * GET /api/payments/{payment}
     */
    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load(['member', 'subscription.plan', 'receiver']));
    }

    /**
     * GET /api/payments/{payment}/receipt?format=json|pdf (json par défaut)
     * 200 ReceiptResource ou PDF ; 422 pdf_unavailable si dompdf n'est pas installé
     */
    public function receipt(Request $request, Payment $payment): ReceiptResource|Response
    {
        $format = $request->validate(['format' => ['sometimes', 'in:json,pdf']])['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->receipts->pdf($payment);
        }

        return new ReceiptResource($payment->load(['member', 'subscription.plan', 'receiver']));
    }

    /**
     * PATCH /api/payments/{payment}/cancel [manager]
     */
    public function cancel(CancelPaymentRequest $request, Payment $payment): PaymentResource
    {
        return new PaymentResource($this->payments->cancel($payment, $request->reason));
    }

    /**
     * GET /api/members/{member}/payments
     */
    public function byMember(Request $request, Member $member): JsonResponse
    {
        $paginator = $this->payments->paginate(['member_id' => $member->id], min((int) $request->integer('per_page', 30), 200));

        return PaymentResource::collection($paginator)->response();
    }
}
