<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Caisse simplifiée (RG-CAI-03, 04) : entrées - sorties = solde, et manquants.
 */
class CashbookService
{
    public function __construct(private readonly LatePaymentService $latePayments) {}

    public function report(Carbon $from, Carbon $to): array
    {
        $fromD = $from->toDateString();
        $toD = $to->toDateString();

        // Entrées
        $incomeRows = Payment::valid()->paidBetween($fromD, $toD)
            ->selectRaw('method, type, SUM(amount) AS total, COUNT(*) AS count')
            ->groupBy('method', 'type')->get();

        $incomeByMethod = array_fill_keys(PaymentMethod::values(), 0);
        $incomeByType = array_fill_keys(PaymentType::values(), 0);
        $incomeTotal = 0;
        $incomeCount = 0;
        foreach ($incomeRows as $r) {
            $incomeByMethod[$r->method->value] += (int) $r->total;
            $incomeByType[$r->type->value] += (int) $r->total;
            $incomeTotal += (int) $r->total;
            $incomeCount += (int) $r->count;
        }

        // Sorties
        $expenseRows = Expense::valid()->spentBetween($fromD, $toD)
            ->join('expense_categories as c', 'c.id', '=', 'expenses.expense_category_id')
            ->selectRaw('c.name AS category, expenses.method, SUM(expenses.amount) AS total, COUNT(*) AS count')
            ->groupBy('c.name', 'expenses.method')->get();

        $expenseByCategory = [];
        $expenseByMethod = array_fill_keys(PaymentMethod::values(), 0);
        $expenseTotal = 0;
        $expenseCount = 0;
        foreach ($expenseRows as $r) {
            $expenseByCategory[$r->category] = ($expenseByCategory[$r->category] ?? 0) + (int) $r->total;
            $expenseByMethod[$r->method->value] += (int) $r->total;
            $expenseTotal += (int) $r->total;
            $expenseCount += (int) $r->count;
        }
        arsort($expenseByCategory);

        // Cumul de l'année
        $yearStart = $to->copy()->startOfYear()->toDateString();
        $ytdIncome = (int) Payment::valid()->paidBetween($yearStart, $toD)->sum('amount');
        $ytdExpenses = (int) Expense::valid()->spentBetween($yearStart, $toD)->sum('amount');

        // Par jour
        $dailyIncome = Payment::valid()->paidBetween($fromD, $toD)
            ->selectRaw('DATE(paid_at) AS d, SUM(amount) AS total')->groupBy('d')->pluck('total', 'd');
        $dailyExpenses = Expense::valid()->spentBetween($fromD, $toD)
            ->selectRaw('spent_on AS d, SUM(amount) AS total')->groupBy('d')->pluck('total', 'd');

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $days[] = ['date' => $key, 'income' => (int) ($dailyIncome[$key] ?? 0), 'expenses' => (int) ($dailyExpenses[$key] ?? 0)];
        }

        return [
            'period' => ['from' => $fromD, 'to' => $toD],
            'income' => ['total' => $incomeTotal, 'count' => $incomeCount, 'by_method' => $incomeByMethod, 'by_type' => $incomeByType],
            'expenses' => ['total' => $expenseTotal, 'count' => $expenseCount, 'by_category' => $expenseByCategory, 'by_method' => $expenseByMethod],
            'balance' => $incomeTotal - $expenseTotal,
            'year_to_date' => ['income' => $ytdIncome, 'expenses' => $ytdExpenses, 'balance' => $ytdIncome - $ytdExpenses],
            'outstanding' => $this->outstanding(),
            'days' => $days,
        ];
    }

    /** Manquants (RG-CAI-04) : abonnements commencés, non annulés, avec solde dû. */
    public function outstanding(): array
    {
        $row = Subscription::query()
            ->where('status', '<>', SubscriptionStatus::Cancelled->value)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->leftJoinSub(
                Payment::valid()->whereNotNull('subscription_id')->selectRaw('subscription_id, SUM(amount) AS paid')->groupBy('subscription_id'),
                'p', 'p.subscription_id', '=', 'subscriptions.id'
            )
            ->selectRaw('SUM(price_at_subscription) AS expected, SUM(COALESCE(p.paid, 0)) AS collected, SUM(GREATEST(price_at_subscription - COALESCE(p.paid, 0), 0)) AS missing, COUNT(*) FILTER (WHERE price_at_subscription - COALESCE(p.paid, 0) > 0) AS unpaid_count')
            ->first();

        $late = $this->latePayments->list();
        $expected = (int) ($row->expected ?? 0);
        $collected = (int) ($row->collected ?? 0);

        return [
            'expected' => $expected,
            'collected' => $collected,
            'missing' => (int) ($row->missing ?? 0),
            'unpaid_subscriptions' => (int) ($row->unpaid_count ?? 0),
            'recovery_rate' => $expected > 0 ? round($collected / $expected, 3) : 1,
            'late_count' => $late->count(),
            'late_amount' => (int) $late->sum('amount_due'),
        ];
    }
}
