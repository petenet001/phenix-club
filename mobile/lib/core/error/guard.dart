import '../result/result.dart';
import 'exceptions.dart';
import 'failures.dart';

/// Exécute un appel de la couche data et traduit les exceptions en [Failure].
Future<Result<T>> guard<T>(Future<T> Function() call) async {
  try {
    return Success(await call());
  } on ApiException catch (e) {
    if (e.isUnauthorized) return const Err(UnauthorizedFailure());
    if (e.isNetwork) return Err(NetworkFailure(e.message));
    return Err(ServerFailure(e.message, code: e.code, fieldErrors: e.fieldErrors));
  } on FormatException {
    return const Err(ServerFailure('Réponse inattendue du serveur.'));
  }
}
