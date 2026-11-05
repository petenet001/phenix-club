import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/network/session_events.dart';
import '../../domain/usecases/auth_usecases.dart';

// Événements
sealed class AuthEvent extends Equatable {
  const AuthEvent();
  @override
  List<Object?> get props => [];
}

class AuthCheckRequested extends AuthEvent {
  const AuthCheckRequested();
}

class AuthLoggedIn extends AuthEvent {
  const AuthLoggedIn();
}

class AuthLogoutRequested extends AuthEvent {
  const AuthLogoutRequested();
}

class AuthSessionExpired extends AuthEvent {
  const AuthSessionExpired();
}

// État
enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState extends Equatable {
  const AuthState({this.status = AuthStatus.unknown, this.message});

  final AuthStatus status;

  /// Raison d'une déconnexion subie (session expirée), affichée sur l'écran de connexion.
  final String? message;

  @override
  List<Object?> get props => [status, message];
}

/// Session de l'adhérent : pilote la redirection du routeur (connexion / application).
class AuthBloc extends Bloc<AuthEvent, AuthState> {
  AuthBloc({
    required CheckSessionUseCase checkSession,
    required LogoutUseCase logout,
    required ClearSessionUseCase clearSession,
    required SessionEvents session,
  }) : super(const AuthState()) {
    on<AuthCheckRequested>((event, emit) async {
      emit(AuthState(status: await checkSession() ? AuthStatus.authenticated : AuthStatus.unauthenticated));
    });
    on<AuthLoggedIn>((event, emit) => emit(const AuthState(status: AuthStatus.authenticated)));
    on<AuthLogoutRequested>((event, emit) async {
      await logout();
      emit(const AuthState(status: AuthStatus.unauthenticated));
    });
    on<AuthSessionExpired>((event, emit) async {
      if (state.status != AuthStatus.authenticated) return;
      await clearSession();
      emit(const AuthState(status: AuthStatus.unauthenticated, message: 'Votre session a expiré, reconnectez-vous.'));
    });

    _expiredSub = session.onExpired.listen((_) => add(const AuthSessionExpired()));
  }

  late final StreamSubscription<void> _expiredSub;

  @override
  Future<void> close() {
    _expiredSub.cancel();
    return super.close();
  }
}
