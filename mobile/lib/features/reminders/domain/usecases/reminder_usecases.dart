import '../../../../core/result/result.dart';
import '../../../programs/domain/entities/program.dart';
import '../../../programs/domain/usecases/get_programs.dart';
import '../entities/reminder_settings.dart';
import '../repositories/reminders_repository.dart';
import '../services/reminder_planner.dart';

class GetReminderSettingsUseCase {
  const GetReminderSettingsUseCase(this._settings);
  final ReminderSettingsRepository _settings;
  ReminderSettings call() => _settings.settings;
}

/// Enregistre les préférences et reprogramme les rappels. Retourne les rappels programmés.
class ApplyRemindersUseCase {
  const ApplyRemindersUseCase(this._settings, this._scheduler, this._planner);
  final ReminderSettingsRepository _settings;
  final NotificationScheduler _scheduler;
  final ReminderPlanner _planner;

  Future<List<SessionReminder>> call(ReminderSettings settings, Program? program) async {
    await _settings.save(settings);
    final reminders = _planner.plan(settings, program);
    await _scheduler.scheduleWeekly(reminders);
    return reminders;
  }
}

/// À la connexion : relit le programme (il a pu changer) et reprogramme si les rappels sont activés.
class SyncRemindersUseCase {
  const SyncRemindersUseCase(this._settings, this._apply, this._getPrograms, this._planner);
  final ReminderSettingsRepository _settings;
  final ApplyRemindersUseCase _apply;
  final GetProgramsUseCase _getPrograms;
  final ReminderPlanner _planner;

  Future<void> call() async {
    final settings = _settings.settings;
    if (!settings.enabled) return;
    final programs = await _getPrograms();
    // Hors ligne : on garde les rappels déjà programmés
    if (programs case Success(value: final list)) {
      await _apply(settings, _planner.activeTraining(list));
    }
  }
}

/// À la déconnexion : plus aucun rappel sur ce téléphone.
class CancelRemindersUseCase {
  const CancelRemindersUseCase(this._scheduler);
  final NotificationScheduler _scheduler;
  Future<void> call() => _scheduler.cancelAll();
}

class RequestNotificationPermissionUseCase {
  const RequestNotificationPermissionUseCase(this._scheduler);
  final NotificationScheduler _scheduler;
  Future<bool> call() => _scheduler.requestPermission();
}

class SendTestReminderUseCase {
  const SendTestReminderUseCase(this._scheduler);
  final NotificationScheduler _scheduler;
  Future<void> call() => _scheduler.showNow(title: 'Rappel de séance', body: 'Les rappels fonctionnent : vous serez prévenu les jours de séance.');
}
