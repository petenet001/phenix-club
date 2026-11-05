import 'package:equatable/equatable.dart';

import '../../../auth/domain/entities/member_profile.dart';

class SubscriptionInfo extends Equatable {
  const SubscriptionInfo({
    required this.plan,
    required this.startDate,
    required this.endDate,
    required this.status,
    required this.statusLabel,
    required this.daysTotal,
    required this.daysLeft,
    this.sessionsCount,
    required this.sessionsUsed,
    required this.price,
    required this.amountPaid,
    required this.balanceDue,
  });

  final String plan;
  final DateTime startDate;
  final DateTime endDate;
  final String status;
  final String statusLabel;
  final int daysTotal;
  final int daysLeft;
  final int? sessionsCount;
  final int sessionsUsed;
  final int price;
  final int amountPaid;
  final int balanceDue;

  bool get isSessionCard => sessionsCount != null;
  int get sessionsLeft => (sessionsCount ?? 0) - sessionsUsed;

  /// Progression 0..1 : séances consommées pour une carte, temps écoulé sinon.
  double get progress {
    if (isSessionCard) {
      return sessionsCount == 0 ? 1 : (sessionsUsed / sessionsCount!).clamp(0, 1);
    }
    return daysTotal == 0 ? 1 : (1 - daysLeft / daysTotal).clamp(0, 1);
  }

  @override
  List<Object?> get props => [
    plan,
    startDate,
    endDate,
    status,
    daysLeft,
    sessionsUsed,
    balanceDue,
  ];
}

/// Peut-on entrer aujourd'hui ? (mêmes règles que le pointage à l'accueil)
class AccessInfo extends Equatable {
  const AccessInfo({
    required this.allowed,
    this.code,
    required this.message,
    required this.warning,
  });

  final bool allowed;
  final String? code;
  final String message;
  final bool warning;

  bool get alreadyCheckedIn => code == 'already_checked_in_today';

  @override
  List<Object?> get props => [allowed, code, message, warning];
}

class ClubInfo extends Equatable {
  const ClubInfo({
    required this.name,
    required this.address,
    required this.phone,
    required this.logoUrl,
    this.whatsappUrl,
  });

  final String name;
  final String address;
  final String phone;
  final String logoUrl;
  final String? whatsappUrl;

  @override
  List<Object?> get props => [name, address, phone, whatsappUrl];
}

class Dashboard extends Equatable {
  const Dashboard({
    required this.profile,
    this.subscription,
    this.upcomingSubscription,
    required this.access,
    required this.totalDue,
    required this.isLate,
    required this.daysLate,
    required this.attendancesThisMonth,
    required this.attendancesTotal,
    this.lastAttendanceAt,
    required this.club,
  });

  final MemberProfile profile;
  final SubscriptionInfo? subscription;
  final SubscriptionInfo? upcomingSubscription;
  final AccessInfo access;
  final int totalDue;
  final bool isLate;
  final int daysLate;
  final int attendancesThisMonth;
  final int attendancesTotal;
  final DateTime? lastAttendanceAt;
  final ClubInfo club;

  @override
  List<Object?> get props => [
    profile,
    subscription,
    upcomingSubscription,
    access,
    totalDue,
    isLate,
    attendancesThisMonth,
    lastAttendanceAt,
  ];
}
