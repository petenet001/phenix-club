import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/failures.dart';
import '../../../auth/domain/usecases/auth_usecases.dart';

sealed class ChangePasswordEvent extends Equatable {
  const ChangePasswordEvent();
  @override
  List<Object?> get props => [];
}

class ChangePasswordSubmitted extends ChangePasswordEvent {
  const ChangePasswordSubmitted({
    required this.current,
    required this.password,
    required this.confirmation,
  });
  final String current;
  final String password;
  final String confirmation;
  @override
  List<Object?> get props => [current, password, confirmation];
}

enum ChangePasswordStatus { idle, submitting, success, failure }

class ChangePasswordState extends Equatable {
  const ChangePasswordState({
    this.status = ChangePasswordStatus.idle,
    this.message,
    this.fieldErrors = const {},
  });
  final ChangePasswordStatus status;
  final String? message;
  final Map<String, String> fieldErrors;
  @override
  List<Object?> get props => [status, message, fieldErrors];
}

class ChangePasswordBloc
    extends Bloc<ChangePasswordEvent, ChangePasswordState> {
  ChangePasswordBloc(ChangePasswordUseCase changePassword)
    : super(const ChangePasswordState()) {
    on<ChangePasswordSubmitted>((event, emit) async {
      if (event.password.length < 6) {
        emit(
          const ChangePasswordState(
            status: ChangePasswordStatus.failure,
            fieldErrors: {'password': 'Au moins 6 caractères.'},
          ),
        );
        return;
      }
      if (event.password != event.confirmation) {
        emit(
          const ChangePasswordState(
            status: ChangePasswordStatus.failure,
            fieldErrors: {
              'password_confirmation': 'Les deux codes ne correspondent pas.',
            },
          ),
        );
        return;
      }
      emit(const ChangePasswordState(status: ChangePasswordStatus.submitting));
      final result = await changePassword(
        current: event.current,
        password: event.password,
        confirmation: event.confirmation,
      );
      emit(
        result.fold(
          (f) => ChangePasswordState(
            status: ChangePasswordStatus.failure,
            message: f.message,
            fieldErrors: f is ServerFailure ? f.fieldErrors : const {},
          ),
          (_) =>
              const ChangePasswordState(status: ChangePasswordStatus.success),
        ),
      );
    });
  }
}
