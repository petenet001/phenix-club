import 'package:equatable/equatable.dart';

class Payment extends Equatable {
  const Payment({
    required this.id,
    required this.receiptNumber,
    required this.paidAt,
    required this.amount,
    required this.type,
    required this.typeLabel,
    required this.methodLabel,
    this.plan,
    this.periodStart,
    this.periodEnd,
    required this.cancelled,
  });

  final int id;
  final String receiptNumber;
  final DateTime paidAt;
  final int amount;

  /// registration | subscription | other
  final String type;
  final String typeLabel;
  final String methodLabel;
  final String? plan;
  final DateTime? periodStart;
  final DateTime? periodEnd;
  final bool cancelled;

  @override
  List<Object?> get props => [id, receiptNumber, amount, cancelled];
}

class Receipt extends Equatable {
  const Receipt({
    required this.receiptNumber,
    required this.paidAt,
    required this.memberName,
    required this.memberCode,
    required this.typeLabel,
    this.plan,
    this.periodStart,
    this.periodEnd,
    required this.amount,
    required this.amountInWords,
    required this.methodLabel,
    this.reference,
    required this.balanceDue,
    this.receivedBy,
    required this.cancelled,
    required this.clubName,
    required this.clubAddress,
  });

  final String receiptNumber;
  final DateTime paidAt;
  final String memberName;
  final String memberCode;
  final String typeLabel;
  final String? plan;
  final DateTime? periodStart;
  final DateTime? periodEnd;
  final int amount;
  final String amountInWords;
  final String methodLabel;
  final String? reference;
  final int balanceDue;
  final String? receivedBy;
  final bool cancelled;
  final String clubName;
  final String clubAddress;

  @override
  List<Object?> get props => [receiptNumber, amount, cancelled];
}

class BalanceItem extends Equatable {
  const BalanceItem({required this.type, required this.label, required this.amount, this.periodStart, this.periodEnd, this.price, this.amountPaid});

  final String type;
  final String label;
  final int amount;
  final DateTime? periodStart;
  final DateTime? periodEnd;
  final int? price;
  final int? amountPaid;

  @override
  List<Object?> get props => [type, label, amount, periodStart];
}

/// Ce que l'adhérent doit et son éventuel retard de renouvellement.
class Balance extends Equatable {
  const Balance({required this.totalDue, required this.items, required this.isLate, required this.daysLate, required this.lateAmount, this.lateMessage});

  final int totalDue;
  final List<BalanceItem> items;
  final bool isLate;
  final int daysLate;

  /// Montant attendu pour se remettre à jour (renouvellement), distinct des soldes ci-dessus.
  final int lateAmount;
  final String? lateMessage;

  bool get isClear => totalDue == 0 && !isLate;

  @override
  List<Object?> get props => [totalDue, items, isLate, daysLate, lateAmount];
}
