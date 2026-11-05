import '../error/failures.dart';

/// Résultat d'un cas d'usage : succès avec une valeur, ou échec avec une [Failure].
sealed class Result<T> {
  const Result();

  R fold<R>(R Function(Failure failure) onFailure, R Function(T value) onSuccess) => switch (this) {
        Success<T>(:final value) => onSuccess(value),
        Err<T>(:final failure) => onFailure(failure),
      };
}

final class Success<T> extends Result<T> {
  const Success(this.value);
  final T value;
}

final class Err<T> extends Result<T> {
  const Err(this.failure);
  final Failure failure;
}
