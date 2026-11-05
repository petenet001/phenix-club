import 'package:flutter/material.dart' show ThemeMode;

/// Préférences locales de l'application (stockées sur le téléphone).
abstract interface class SettingsRepository {
  ThemeMode get themeMode;

  Future<void> saveThemeMode(ThemeMode mode);
}
