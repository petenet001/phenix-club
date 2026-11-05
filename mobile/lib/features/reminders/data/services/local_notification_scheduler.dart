import 'dart:io' show Platform;

import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_timezone/flutter_timezone.dart';
import 'package:timezone/data/latest_all.dart' as tzdata;
import 'package:timezone/timezone.dart' as tz;

import '../../domain/entities/reminder_settings.dart';
import '../../domain/repositories/reminders_repository.dart';

class LocalNotificationScheduler implements NotificationScheduler {
  LocalNotificationScheduler([FlutterLocalNotificationsPlugin? plugin]) : _plugin = plugin ?? FlutterLocalNotificationsPlugin();

  final FlutterLocalNotificationsPlugin _plugin;

  static const _details = NotificationDetails(
    android: AndroidNotificationDetails(
      'session_reminders',
      'Rappels de séance',
      channelDescription: 'Rappel les jours de séance de votre programme',
      importance: Importance.high,
      priority: Priority.high,
    ),
    iOS: DarwinNotificationDetails(),
  );

  /// Fuseau horaire du téléphone et initialisation du plugin (sans demander l'autorisation).
  Future<void> init() async {
    tzdata.initializeTimeZones();
    try {
      final local = await FlutterTimezone.getLocalTimezone();
      tz.setLocalLocation(tz.getLocation(local.identifier));
    } catch (_) {
      tz.setLocalLocation(tz.getLocation('Africa/Brazzaville'));
    }
    await _plugin.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
        iOS: DarwinInitializationSettings(requestAlertPermission: false, requestBadgePermission: false, requestSoundPermission: false),
      ),
    );
  }

  @override
  Future<bool> requestPermission() async {
    if (Platform.isIOS) {
      final ios = _plugin.resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>();
      return await ios?.requestPermissions(alert: true, badge: true, sound: true) ?? false;
    }
    final android = _plugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    return await android?.requestNotificationsPermission() ?? true;
  }

  @override
  Future<void> scheduleWeekly(List<SessionReminder> reminders) async {
    await _plugin.cancelAll();
    for (final r in reminders) {
      await _plugin.zonedSchedule(
        id: r.id,
        title: r.title,
        body: r.body,
        scheduledDate: nextInstance(r.weekday, r.hour, r.minute, tz.TZDateTime.now(tz.local)),
        notificationDetails: _details,
        // Sans alarme exacte : pas d'autorisation spéciale Android, quelques minutes de marge possibles
        androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
        matchDateTimeComponents: DateTimeComponents.dayOfWeekAndTime,
      );
    }
  }

  /// Prochaine occurrence du jour et de l'heure donnés, strictement après [now].
  static tz.TZDateTime nextInstance(int weekday, int hour, int minute, tz.TZDateTime now) {
    var date = tz.TZDateTime(now.location, now.year, now.month, now.day, hour, minute);
    while (date.weekday != weekday || !date.isAfter(now)) {
      date = date.add(const Duration(days: 1));
    }
    return date;
  }

  @override
  Future<void> cancelAll() => _plugin.cancelAll();

  @override
  Future<void> showNow({required String title, required String body}) => _plugin.show(id: 1, title: title, body: body, notificationDetails: _details);

  @override
  Future<int> pendingCount() async => (await _plugin.pendingNotificationRequests()).length;
}
