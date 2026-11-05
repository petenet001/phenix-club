<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\ReminderType;
use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LatePaymentService
{
    /**
     * Liste des adhérents en retard (RG-RET-01 à 03), calculée en SQL.
     * Chaque ligne : member (id, code, full_name, phone, status), end_date, days_late, amount_due, balance_due, plan, last_reminder_at
     */
    public function list(int $minDays = 0): Collection
    {
        $lateDays = Setting::latePaymentDays();
        $today = now()->toDateString();

        $rows = DB::select(<<<SQL
            WITH last_sub AS (
                SELECT DISTINCT ON (s.member_id) s.*
                FROM subscriptions s
                WHERE s.status <> :cancelled
                ORDER BY s.member_id, s.end_date DESC
            ),
            paid AS (
                SELECT subscription_id, COALESCE(SUM(amount), 0) AS amount_paid
                FROM payments
                WHERE cancelled_at IS NULL AND subscription_id IS NOT NULL
                GROUP BY subscription_id
            ),
            last_rem AS (
                SELECT member_id, MAX(sent_at) AS last_reminder_at
                FROM reminders
                WHERE type = :reminder_type AND status = 'sent'
                GROUP BY member_id
            )
            SELECT m.id, m.code, m.first_name, m.last_name, m.phone, m.status, m.photo_path,
                   ls.id AS subscription_id, ls.end_date, ls.price_at_subscription, sp.name AS plan_name,
                   (ls.price_at_subscription - COALESCE(p.amount_paid, 0)) AS balance_due,
                   (CURRENT_DATE - ls.end_date - :late_days) AS days_late,
                   lr.last_reminder_at
            FROM members m
            JOIN last_sub ls ON ls.member_id = m.id
            JOIN subscription_plans sp ON sp.id = ls.subscription_plan_id
            LEFT JOIN paid p ON p.subscription_id = ls.id
            LEFT JOIN last_rem lr ON lr.member_id = m.id
            WHERE m.deleted_at IS NULL
              AND m.status NOT IN (:suspended, :pending)
              AND ls.end_date + make_interval(days => :late_days2) < CAST(:today AS date)
              AND NOT EXISTS (
                  SELECT 1 FROM subscriptions s2
                  WHERE s2.member_id = m.id AND s2.status IN (:active, :pending_sub)
              )
              AND (CURRENT_DATE - ls.end_date - :late_days3) >= :min_days
            ORDER BY days_late DESC
        SQL, [
            'cancelled' => SubscriptionStatus::Cancelled->value,
            'reminder_type' => ReminderType::LatePayment->value,
            'late_days' => $lateDays,
            'late_days2' => $lateDays,
            'late_days3' => $lateDays,
            'suspended' => MemberStatus::Suspended->value,
            'pending' => MemberStatus::Pending->value,
            'today' => $today,
            'active' => SubscriptionStatus::Active->value,
            'pending_sub' => SubscriptionStatus::Pending->value,
            'min_days' => $minDays,
        ]);

        return collect($rows)->map(function ($r) {
            $balance = max(0, (int) $r->balance_due);

            return [
                'member' => [
                    'id' => $r->id,
                    'code' => $r->code,
                    'full_name' => trim($r->first_name.' '.$r->last_name),
                    'phone' => $r->phone,
                    'status' => $r->status,
                ],
                'subscription_id' => $r->subscription_id,
                'plan' => $r->plan_name,
                'end_date' => $r->end_date,
                'days_late' => (int) $r->days_late,
                'balance_due' => $balance,
                'amount_due' => $balance > 0 ? $balance : (int) $r->price_at_subscription,
                'reason' => $balance > 0 ? 'unpaid_balance' : 'renewal_expected',
                'last_reminder_at' => $r->last_reminder_at,
            ];
        });
    }

    public function summary(): array
    {
        $list = $this->list();
        $active = Member::where('status', MemberStatus::Active->value)->count();

        return [
            'count' => $list->count(),
            'amount' => (int) $list->sum('amount_due'),
            'rate' => ($active + $list->count()) > 0 ? round($list->count() / ($active + $list->count()), 3) : 0,
        ];
    }
}
