import '../../../../core/utils/formatters.dart';
import '../../../auth/data/models/member_profile_model.dart';
import '../../domain/entities/dashboard.dart';

int _int(Object? v) => (v as num?)?.toInt() ?? 0;

class DashboardModel {
  const DashboardModel._();

  static SubscriptionInfo subscription(Map<String, dynamic> j) =>
      SubscriptionInfo(
        plan: j['plan'] as String? ?? '',
        startDate: Fmt.parse(j['start_date'])!,
        endDate: Fmt.parse(j['end_date'])!,
        status: j['status'] as String? ?? '',
        statusLabel: j['status_label'] as String? ?? '',
        daysTotal: _int(j['days_total']),
        daysLeft: _int(j['days_left']),
        sessionsCount: (j['sessions_count'] as num?)?.toInt(),
        sessionsUsed: _int(j['sessions_used']),
        price: _int(j['price']),
        amountPaid: _int(j['amount_paid']),
        balanceDue: _int(j['balance_due']),
      );

  static ClubInfo club(Map<String, dynamic> j) => ClubInfo(
    name: j['name'] as String? ?? 'Phenix Club',
    address: j['address'] as String? ?? '',
    phone: j['phone'] as String? ?? '',
    logoUrl: j['logo_url'] as String? ?? '',
    whatsappUrl: j['whatsapp_url'] as String?,
  );

  static Dashboard fromJson(Map<String, dynamic> j) {
    final access = j['access'] as Map<String, dynamic>;
    final balance = j['balance'] as Map<String, dynamic>;
    final stats = j['stats'] as Map<String, dynamic>;
    return Dashboard(
      profile: MemberProfileModel.fromJson(
        j['profile'] as Map<String, dynamic>,
      ),
      subscription: j['subscription'] == null
          ? null
          : subscription(j['subscription'] as Map<String, dynamic>),
      upcomingSubscription: j['upcoming_subscription'] == null
          ? null
          : subscription(j['upcoming_subscription'] as Map<String, dynamic>),
      access: AccessInfo(
        allowed: access['allowed'] == true,
        code: access['code'] as String?,
        message: access['message'] as String? ?? '',
        warning: access['warning'] == true,
      ),
      totalDue: _int(balance['total_due']),
      isLate: balance['is_late'] == true,
      daysLate: _int(balance['days_late']),
      attendancesThisMonth: _int(stats['attendances_this_month']),
      attendancesTotal: _int(stats['attendances_total']),
      lastAttendanceAt: Fmt.parse(stats['last_attendance_at']),
      club: club(j['club'] as Map<String, dynamic>),
    );
  }
}
