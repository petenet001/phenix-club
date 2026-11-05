<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\ReminderType;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbsenceService
{
    /**
     * Adhérents actifs sans pointage depuis N jours (RG-ABS-01), hors retards de paiement (RG-ABS-02).
     */
    public function list(?int $days = null): Collection
    {
        $days = $days ?? Setting::inactivityDays();
        $lateIds = app(LatePaymentService::class)->list()->pluck('member.id')->all();

        $rows = DB::table('members as m')
            ->leftJoin('attendances as a', 'a.member_id', '=', 'm.id')
            ->leftJoin(DB::raw("(SELECT member_id, MAX(sent_at) AS last_reminder_at FROM reminders WHERE type = '".ReminderType::Absence->value."' AND status = 'sent' GROUP BY member_id) lr"), 'lr.member_id', '=', 'm.id')
            ->select('m.id', 'm.code', 'm.first_name', 'm.last_name', 'm.phone', 'm.joined_at', DB::raw('MAX(a.attended_on) AS last_attendance'), DB::raw('MAX(lr.last_reminder_at) AS last_reminder_at'))
            ->whereNull('m.deleted_at')
            ->where('m.status', MemberStatus::Active->value)
            ->when(! empty($lateIds), fn ($q) => $q->whereNotIn('m.id', $lateIds))
            ->groupBy('m.id', 'm.code', 'm.first_name', 'm.last_name', 'm.phone', 'm.joined_at')
            ->havingRaw('COALESCE(MAX(a.attended_on), m.joined_at) < ?', [now()->subDays($days)->toDateString()])
            ->orderByRaw('MAX(a.attended_on) ASC NULLS FIRST')
            ->get();

        return $rows->map(fn ($r) => [
            'member' => [
                'id' => $r->id,
                'code' => $r->code,
                'full_name' => trim($r->first_name.' '.$r->last_name),
                'phone' => $r->phone,
            ],
            'last_attendance_on' => $r->last_attendance,
            'days_absent' => $r->last_attendance
                ? (int) \Carbon\Carbon::parse($r->last_attendance)->diffInDays(now()->startOfDay())
                : (int) \Carbon\Carbon::parse($r->joined_at)->diffInDays(now()->startOfDay()),
            'never_attended' => $r->last_attendance === null,
            'last_reminder_at' => $r->last_reminder_at,
        ]);
    }
}
