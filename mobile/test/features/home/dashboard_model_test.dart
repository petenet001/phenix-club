import 'package:flutter_test/flutter_test.dart';
import 'package:phenix_client/features/home/data/models/dashboard_model.dart';

void main() {
  test('lit l\'écran d\'accueil renvoyé par /member/me', () {
    final d = DashboardModel.fromJson({
      'profile': {'id': 4, 'code': 'PHX-000004', 'first_name': 'Prisca', 'last_name': 'Samba', 'full_name': 'Prisca Samba', 'phone': '+242061000004', 'status': 'active', 'status_label': 'Actif', 'joined_at': '2026-10-12', 'qr_token': 'abc'},
      'subscription': {'plan': 'Trimestriel', 'start_date': '2026-10-12', 'end_date': '2027-01-09', 'status': 'active', 'status_label': 'En cours', 'days_total': 90, 'days_left': 67, 'sessions_count': null, 'sessions_used': 0, 'price': 40000, 'amount_paid': 25000, 'balance_due': 15000},
      'upcoming_subscription': null,
      'access': {'allowed': false, 'code': 'already_checked_in_today', 'message': 'Déjà pointé', 'warning': false},
      'balance': {'total_due': 20000, 'is_late': false, 'days_late': 0},
      'stats': {'attendances_this_month': 3, 'attendances_total': 10, 'last_attendance_at': '2026-10-30T19:15:00+01:00'},
      'club': {'name': 'Phenix Club', 'address': 'Brazzaville', 'phone': '', 'logo_url': 'x', 'whatsapp_url': null},
    });

    expect(d.profile.initials, 'PS');
    expect(d.subscription!.daysLeft, 67);
    expect(d.subscription!.progress, closeTo(23 / 90, .001));
    expect(d.access.alreadyCheckedIn, isTrue);
    expect(d.totalDue, 20000);
    expect(d.lastAttendanceAt, isNotNull);
  });
}
