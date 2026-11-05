import 'dart:async';

/// Diffuse l'expiration de session détectée par le client HTTP (401) vers l'AuthBloc,
/// sans que la couche réseau dépende de la présentation.
class SessionEvents {
  final _expired = StreamController<void>.broadcast();

  Stream<void> get onExpired => _expired.stream;

  void notifyExpired() => _expired.add(null);

  Future<void> dispose() => _expired.close();
}
