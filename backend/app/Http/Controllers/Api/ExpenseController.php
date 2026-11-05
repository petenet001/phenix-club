<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Payment\CancelPaymentRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * GET /api/expenses?from=&to=&category_id=&method=&search=&page=
     * Réponse : liste paginée + meta.totals { total, count, by_category }
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $query = Expense::query()->with(['category', 'recorder'])->spentBetween($from, $to);

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn ($q) => $q->where('label', 'ilike', "%{$term}%")->orWhere('payee', 'ilike', "%{$term}%")->orWhere('reference', 'ilike', "%{$term}%"));
        }
        if (! $request->boolean('include_cancelled')) {
            $query->valid();
        }

        $totals = (clone $query)->valid()
            ->join('expense_categories as c', 'c.id', '=', 'expenses.expense_category_id')
            ->selectRaw('c.name AS category, SUM(expenses.amount) AS total, COUNT(*) AS count')
            ->groupBy('c.name')->get();

        return ExpenseResource::collection($query->orderByDesc('spent_on')->orderByDesc('id')->paginate(min((int) $request->integer('per_page', 30), 200))->withQueryString())
            ->additional(['meta' => ['totals' => [
                'total' => (int) $totals->sum('total'),
                'count' => (int) $totals->sum('count'),
                'by_category' => $totals->pluck('total', 'category')->map(fn ($v) => (int) $v),
            ]]])
            ->response();
    }

    /**
     * POST /api/expenses (multipart si justificatif)
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->safe()->except('receipt');
        $data['recorded_by'] = $request->user()->id;

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense = Expense::create($data)->load(['category', 'recorder']);

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load(['category', 'recorder']));
    }

    /**
     * PUT /api/expenses/{expense} - modification tant que non annulée (agent ou gérant)
     */
    public function update(StoreExpenseRequest $request, Expense $expense): ExpenseResource
    {
        if ($expense->isCancelled()) {
            throw new BusinessRuleException('expense_cancelled', 'Dépense annulée, non modifiable.');
        }

        $data = $request->safe()->except('receipt');
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->update($data);

        return new ExpenseResource($expense->refresh()->load(['category', 'recorder']));
    }

    /**
     * PATCH /api/expenses/{expense}/cancel [manager] (RG-CAI-02)
     */
    public function cancel(CancelPaymentRequest $request, Expense $expense): ExpenseResource
    {
        if ($expense->isCancelled()) {
            throw new BusinessRuleException('already_cancelled', 'Cette dépense est déjà annulée.');
        }

        $expense->update(['cancelled_at' => now(), 'cancel_reason' => $request->reason]);

        return new ExpenseResource($expense->refresh()->load(['category', 'recorder']));
    }
}
