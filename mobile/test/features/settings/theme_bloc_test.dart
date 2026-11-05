import 'package:bloc_test/bloc_test.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:phenix_client/features/settings/data/repositories/settings_repository_impl.dart';
import 'package:phenix_client/features/settings/domain/usecases/theme_usecases.dart';
import 'package:phenix_client/features/settings/presentation/bloc/theme_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  Future<ThemeBloc> build(Map<String, Object> stored) async {
    SharedPreferences.setMockInitialValues(stored);
    final repo = SettingsRepositoryImpl(await SharedPreferences.getInstance());
    return ThemeBloc(getThemeMode: GetThemeModeUseCase(repo), saveThemeMode: SaveThemeModeUseCase(repo));
  }

  test('suit le réglage du téléphone par défaut', () async {
    expect((await build({})).state, ThemeMode.system);
  });

  test('reprend le choix enregistré', () async {
    expect((await build({'theme_mode': 'dark'})).state, ThemeMode.dark);
  });

  late ThemeBloc bloc;
  blocTest<ThemeBloc, ThemeMode>(
    'passe en clair et le mémorise',
    setUp: () async => bloc = await build({}),
    build: () => bloc,
    act: (b) => b.add(const ThemeModeChanged(ThemeMode.light)),
    expect: () => [ThemeMode.light],
    verify: (_) async => expect((await SharedPreferences.getInstance()).getString('theme_mode'), 'light'),
  );
}
