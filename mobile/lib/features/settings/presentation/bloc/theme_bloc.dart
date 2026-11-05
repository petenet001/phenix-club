import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart' show ThemeMode;
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/usecases/theme_usecases.dart';

sealed class ThemeEvent extends Equatable {
  const ThemeEvent();
  @override
  List<Object?> get props => [];
}

class ThemeModeChanged extends ThemeEvent {
  const ThemeModeChanged(this.mode);
  final ThemeMode mode;
  @override
  List<Object?> get props => [mode];
}

/// Apparence choisie par l'adhérent : système (défaut), clair ou sombre. État = ThemeMode.
class ThemeBloc extends Bloc<ThemeEvent, ThemeMode> {
  ThemeBloc({
    required GetThemeModeUseCase getThemeMode,
    required SaveThemeModeUseCase saveThemeMode,
  }) : super(getThemeMode()) {
    on<ThemeModeChanged>((event, emit) async {
      emit(event.mode);
      await saveThemeMode(event.mode);
    });
  }
}
