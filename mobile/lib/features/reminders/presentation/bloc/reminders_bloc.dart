import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/result/result.dart';
import '../../../programs/domain/entities/program.dart';
import '../../../programs/domain/usecases/get_programs.dart';
import '../../domain/entities/reminder_settings.dart';
import '../../domain/services/reminder_planner.dart';
import '../../domain/usecases/reminder_usecases.dart';

sealed class RemindersEvent extends Equatable {
  const RemindersEvent();
  @override
  List<Object?> get props => [];
}

class RemindersStarted extends RemindersEvent {
  const RemindersStarted();
}

class RemindersToggled extends RemindersEvent {
  const RemindersToggled(this.enabled);
  final bool enabled;
  @override
  List<Object?> get props => [enabled];
}

class RemindersTimeChanged extends RemindersEvent {
  const RemindersTimeChanged(this.hour, this.minute);
  final int hour;
  final int minute;
  @override
  List<Object?> get props => [hour, minute];
}

class RemindersDayToggled extends RemindersEvent {
  const RemindersDayToggled(this.weekday);
  final int weekday;
  @override
  List<Object?> get props => [weekday];
}

/// Revenir aux jours du programme du coach.
class RemindersProgramDaysRestored extends RemindersEvent {
  const RemindersProgramDaysRestored();
}

class RemindersTestRequested extends RemindersEvent {
  const RemindersTestRequested();
}

class RemindersState extends Equatable {
  const RemindersState({this.loading = true, this.settings = const ReminderSettings(), this.program, this.programDays = const {}, this.days = const {}, this.message});

  final bool loading;
  final ReminderSettings settings;
  final Program? program;
  final Set<int> programDays;

  /// Jours effectivement rappelés (personnalisés ou ceux du programme).
  final Set<int> days;

  /// Information ponctuelle (autorisation refusée, rappel test envoyé…).
  final String? message;

  bool get usesProgramDays => settings.customDays == null;

  RemindersState copyWith({bool? loading, ReminderSettings? settings, Program? program, Set<int>? programDays, Set<int>? days, String? message}) => RemindersState(
        loading: loading ?? this.loading,
        settings: settings ?? this.settings,
        program: program ?? this.program,
        programDays: programDays ?? this.programDays,
        days: days ?? this.days,
        message: message,
      );

  @override
  List<Object?> get props => [loading, settings, program, programDays, days, message];
}

class RemindersBloc extends Bloc<RemindersEvent, RemindersState> {
  RemindersBloc({
    required GetReminderSettingsUseCase getSettings,
    required ApplyRemindersUseCase apply,
    required RequestNotificationPermissionUseCase requestPermission,
    required SendTestReminderUseCase sendTest,
    required GetProgramsUseCase getPrograms,
    ReminderPlanner planner = const ReminderPlanner(),
  }) : super(const RemindersState()) {
    Future<void> save(ReminderSettings settings, Emitter<RemindersState> emit, {String? message}) async {
      await apply(settings, state.program);
      emit(state.copyWith(settings: settings, days: planner.effectiveDays(settings, state.program), message: message));
    }

    on<RemindersStarted>((event, emit) async {
      final settings = getSettings();
      final programs = await getPrograms();
      final program = programs is Success<List<Program>> ? planner.activeTraining(programs.value) : null;
      final programDays = planner.programDays(program);
      emit(RemindersState(loading: false, settings: settings, program: program, programDays: programDays, days: planner.effectiveDays(settings, program)));
    });

    on<RemindersToggled>((event, emit) async {
      if (event.enabled && !await requestPermission()) {
        emit(state.copyWith(message: 'Notifications refusées : autorisez-les pour Phenix Club dans les réglages du téléphone.'));
        return;
      }
      await save(state.settings.copyWith(enabled: event.enabled), emit);
    });

    on<RemindersTimeChanged>((event, emit) => save(state.settings.copyWith(hour: event.hour, minute: event.minute), emit));

    on<RemindersDayToggled>((event, emit) {
      final days = {...state.days};
      days.contains(event.weekday) ? days.remove(event.weekday) : days.add(event.weekday);
      return save(state.settings.copyWith(customDays: days), emit);
    });

    on<RemindersProgramDaysRestored>((event, emit) => save(state.settings.copyWith(resetDays: true), emit));

    on<RemindersTestRequested>((event, emit) async {
      if (!await requestPermission()) {
        emit(state.copyWith(message: 'Notifications refusées : autorisez-les dans les réglages du téléphone.'));
        return;
      }
      await sendTest();
      emit(state.copyWith(message: 'Rappel test envoyé.'));
    });
  }
}
