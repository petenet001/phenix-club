import 'package:equatable/equatable.dart';

/// Erreur métier ou technique remontée par la couche data, sans dépendance à Dio.
sealed class Failure extends Equatable {
  const Failure(this.message);

  final String message;

  @override
  List<Object?> get props => [message];
}

/// Pas de réseau, serveur injoignable ou délai dépassé.
class NetworkFailure extends Failure {
  const NetworkFailure([super.message = 'Impossible de joindre le serveur. Vérifiez votre connexion.']);
}

/// Session expirée ou accès retiré par l'accueil.
class UnauthorizedFailure extends Failure {
  const UnauthorizedFailure([super.message = 'Votre session a expiré, reconnectez-vous.']);
}

/// Refus de l'API avec un code stable (ex : `already_checked_in_today`, `invalid_checkin_code`).
class ServerFailure extends Failure {
  const ServerFailure(super.message, {this.code, this.fieldErrors = const {}});

  final String? code;
  final Map<String, String> fieldErrors;

  @override
  List<Object?> get props => [message, code, fieldErrors];
}
