import 'dart:io' show Platform;

/// Configuration de l'application.
///
/// L'URL de l'API se passe au lancement :
/// `flutter run --dart-define=API_URL=http://192.168.1.20:8000/api`.
/// Par défaut : simulateur iOS -> localhost, émulateur Android -> 10.0.2.2 (la machine hôte).
class AppConfig {
  const AppConfig._();

  static const _fromEnv = String.fromEnvironment('API_URL');

  static String get apiUrl {
    if (_fromEnv.isNotEmpty) return _fromEnv;
    return Platform.isAndroid ? 'http://10.0.2.2:8000/api' : 'http://localhost:8000/api';
  }

  /// Préfixe du QR code affiché sur la borne de la salle (voir KioskTokenService côté API).
  static const checkInQrPrefix = 'PHENIX-CHECKIN:';
}
