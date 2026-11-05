import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/failures.dart';
import '../../domain/usecases/auth_usecases.dart';

sealed class LoginEvent extends Equatable {
  const LoginEvent();
  @override
  List<Object?> get props => [];
}

class LoginSubmitted extends LoginEvent {
  const LoginSubmitted({required this.phone, required this.password});
  final String phone;
  final String password;
  @override
  List<Object?> get props => [phone, password];
}

enum LoginStatus { idle, submitting, success, failure }

class LoginState extends Equatable {
  const LoginState({this.status = LoginStatus.idle, this.message});
  final LoginStatus status;
  final String? message;
  @override
  List<Object?> get props => [status, message];
}

class LoginBloc extends Bloc<LoginEvent, LoginState> {
  LoginBloc(LoginUseCase login) : super(const LoginState()) {
    on<LoginSubmitted>((event, emit) async {
      if (event.phone.trim().isEmpty || event.password.isEmpty) {
        emit(const LoginState(status: LoginStatus.failure, message: 'Saisissez votre téléphone et votre code d\'accès.'));
        return;
      }
      emit(const LoginState(status: LoginStatus.submitting));
      final result = await login(phone: event.phone, password: event.password);
      emit(result.fold(
        (Failure f) => LoginState(status: LoginStatus.failure, message: f.message),
        (_) => const LoginState(status: LoginStatus.success),
      ));
    });
  }
}
