import 'package:equatable/equatable.dart';

class CheckInResult extends Equatable {
  const CheckInResult({required this.checkedInAt, this.plan, this.sessionsCount, this.sessionsUsed, this.warning, required this.message});

  final DateTime checkedInAt;
  final String? plan;
  final int? sessionsCount;
  final int? sessionsUsed;

  /// Accès accordé dans la tolérance après échéance : paiement attendu.
  final String? warning;
  final String message;

  @override
  List<Object?> get props => [checkedInAt, plan, sessionsUsed, warning];
}
