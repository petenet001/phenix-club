import '../entities/reminder_settings.dart';

abstract interface class ReminderSettingsRepository {
  ReminderSettings get settings;

  Future<void> save(ReminderSettings settings);
}

/// Notifications locales du téléphone (implémentée avec flutter_local_notifications).
abstract interface class NotificationScheduler {
  /// Demande l'autorisation d'afficher des notifications ; false si refusée.
  Future<bool> requestPermission();

  /// Remplace tous les rappels programmés par ceux-ci (répétés chaque semaine).
  Future<void> scheduleWeekly(List<SessionReminder> reminders);

  Future<void> cancelAll();

  Future<void> showNow({required String title, required String body});

  Future<int> pendingCount();
}
