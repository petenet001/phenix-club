import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/failures.dart';
import '../../domain/entities/check_in_result.dart';
import '../../domain/usecases/check_in.dart';

sealed class CheckInEvent extends Equatable {
  const CheckInEvent();
  @override
  List<Object?> get props => [];
}

class CheckInCodeScanned extends CheckInEvent {
  const CheckInCodeScanned(this.code);
  final String code;
  @override
  List<Object?> get props => [code];
}

/// Revenir au scan après un refus (ex : QR expiré, rescanner).
class CheckInRetried extends CheckInEvent {
  const CheckInRetried();
}

enum CheckInStatus { scanning, submitting, success, failure }

class CheckInState extends Equatable {
  const CheckInState({this.status = CheckInStatus.scanning, this.result, this.message, this.code});

  final CheckInStatus status;
  final CheckInResult? result;
  final String? message;

  /// Code de refus de l'API (already_checked_in_today, subscription_expired, invalid_checkin_code...).
  final String? code;

  /// Un nouveau scan peut corriger le problème (QR expiré ou mauvais QR).
  bool get canRescan => code == 'invalid_checkin_code' || code == CheckInUseCase.notClubQr || code == null;

  @override
  List<Object?> get props => [status, result, message, code];
}

class CheckInBloc extends Bloc<CheckInEvent, CheckInState> {
  CheckInBloc(CheckInUseCase checkIn) : super(const CheckInState()) {
    on<CheckInCodeScanned>((event, emit) async {
      // La caméra détecte le même code plusieurs fois par seconde : un seul envoi
      if (state.status != CheckInStatus.scanning) return;
      emit(const CheckInState(status: CheckInStatus.submitting));
      final result = await checkIn(event.code);
      emit(result.fold(
        (f) => CheckInState(status: CheckInStatus.failure, message: f.message, code: f is ServerFailure ? f.code : null),
        (r) => CheckInState(status: CheckInStatus.success, result: r),
      ));
    });
    on<CheckInRetried>((event, emit) => emit(const CheckInState()));
  }
}
