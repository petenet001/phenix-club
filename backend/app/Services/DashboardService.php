<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TrainingProgram;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(private readonly LatePaymentService $latePayments) {}

    public function build(Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $statusCounts = Member::query()->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');

        $expiringSoon = Subscription::currentOrUpcoming()
            ->whereBetween('end_date', [$today, now()->addDays(7)->toDateString()])
            ->count();

        $last30 = Attendance::query()
            ->whereBetween('attended_on', [now()->subDays(29)->toDateString(), $today])
            ->selectRaw('attended_on AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');
        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $series[] = ['date' => $d, 'count' => (int) ($last30[$d] ?? 0)];
        }

        $byMethod = array_fill_keys(PaymentMethod::values(), 0);
        foreach (Payment::valid()->paidBetween($monthStart->toDateString(), $monthEnd->toDateString())->selectRaw('method, SUM(amount) AS t')->groupBy('method')->get() as $r) {
            $byMethod[$r->method->value] = (int) $r->t;
        }

        $monthIncome = array_sum($byMethod);
        $monthExpenses = (int) Expense::valid()->spentBetween($monthStart->toDateString(), $monthEnd->toDateString())->sum('amount');
        $late = $this->latePayments->summary();

        return [
            'month' => $month->format('Y-m'),
            'members' => [
                'active' => (int) ($statusCounts[MemberStatus::Active->value] ?? 0),
                'inactive' => (int) ($statusCounts[MemberStatus::Inactive->value] ?? 0),
                'pending' => (int) ($statusCounts[MemberStatus::Pending->value] ?? 0),
                'suspended' => (int) ($statusCounts[MemberStatus::Suspended->value] ?? 0),
                'new_this_month' => Member::whereBetween('joined_at', [$monthStart->toDateString(), $monthEnd->toDateString()])->count(),
                'expiring_within_7_days' => $expiringSoon,
            ],
            'attendance' => [
                'today' => Attendance::onDate($today)->count(),
                'this_week' => Attendance::between($weekStart, $today)->count(),
                'this_month' => Attendance::between($monthStart->toDateString(), $monthEnd->toDateString())->count(),
                'last_30_days' => $series,
            ],
            'revenue' => [
                'today' => (int) Payment::valid()->paidBetween($today, $today)->sum('amount'),
                'this_month' => $monthIncome,
                'by_method' => $byMethod,
                'expenses_this_month' => $monthExpenses,
                'balance_this_month' => $monthIncome - $monthExpenses,
            ],
            'late_payments' => $late,
            'programs' => [
                'pending_review' => TrainingProgram::where('status', 'proposed')->count(),
            ],
        ];
    }
}
