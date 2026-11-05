import 'package:flutter/material.dart' show ThemeMode;
import 'package:shared_preferences/shared_preferences.dart';

import '../../domain/repositories/settings_repository.dart';

class SettingsRepositoryImpl implements SettingsRepository {
  const SettingsRepositoryImpl(this._prefs);

  final SharedPreferences _prefs;
  static const _themeKey = 'theme_mode';

  @override
  ThemeMode get themeMode =>
      ThemeMode.values.asNameMap()[_prefs.getString(_themeKey)] ??
      ThemeMode.system;

  @override
  Future<void> saveThemeMode(ThemeMode mode) =>
      _prefs.setString(_themeKey, mode.name);
}
