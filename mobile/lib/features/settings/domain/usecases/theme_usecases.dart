import 'package:flutter/material.dart' show ThemeMode;

import '../repositories/settings_repository.dart';

class GetThemeModeUseCase {
  const GetThemeModeUseCase(this._repository);
  final SettingsRepository _repository;
  ThemeMode call() => _repository.themeMode;
}

class SaveThemeModeUseCase {
  const SaveThemeModeUseCase(this._repository);
  final SettingsRepository _repository;
  Future<void> call(ThemeMode mode) => _repository.saveThemeMode(mode);
}
