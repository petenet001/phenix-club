import 'package:shared_preferences/shared_preferences.dart';

import '../../domain/entities/reminder_settings.dart';
import '../../domain/repositories/reminders_repository.dart';

class ReminderSettingsRepositoryImpl implements ReminderSettingsRepository {
  const ReminderSettingsRepositoryImpl(this._prefs);
  final SharedPreferences _prefs;

  static const _enabled = 'reminders_enabled';
  static const _hour = 'reminders_hour';
  static const _minute = 'reminders_minute';
  static const _days = 'reminders_days';

  @override
  ReminderSettings get settings {
    final days = _prefs.getStringList(_days);
    return ReminderSettings(
      enabled: _prefs.getBool(_enabled) ?? false,
      hour: _prefs.getInt(_hour) ?? 17,
      minute: _prefs.getInt(_minute) ?? 0,
      customDays: days?.map(int.parse).toSet(),
    );
  }

  @override
  Future<void> save(ReminderSettings s) async {
    await _prefs.setBool(_enabled, s.enabled);
    await _prefs.setInt(_hour, s.hour);
    await _prefs.setInt(_minute, s.minute);
    if (s.customDays == null) {
      await _prefs.remove(_days);
    } else {
      await _prefs.setStringList(_days, s.customDays!.map((d) => '$d').toList());
    }
  }
}
