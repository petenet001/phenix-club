import 'package:equatable/equatable.dart';

/// Préférences de rappel de séance (stockées sur le téléphone).
class ReminderSettings extends Equatable {
  const ReminderSettings({this.enabled = false, this.hour = 17, this.minute = 0, this.customDays});

  final bool enabled;
  final int hour;
  final int minute;

  /// Jours choisis par l'adhérent (1 = lundi … 7 = dimanche) ; null = jours du programme.
  final Set<int>? customDays;

  ReminderSettings copyWith({bool? enabled, int? hour, int? minute, Set<int>? customDays, bool resetDays = false}) => ReminderSettings(
        enabled: enabled ?? this.enabled,
        hour: hour ?? this.hour,
        minute: minute ?? this.minute,
        customDays: resetDays ? null : (customDays ?? this.customDays),
      );

  @override
  List<Object?> get props => [enabled, hour, minute, customDays];
}

/// Un rappel hebdomadaire à programmer.
class SessionReminder extends Equatable {
  const SessionReminder({required this.weekday, required this.hour, required this.minute, required this.title, required this.body});

  /// 1 = lundi … 7 = dimanche (DateTime.monday … DateTime.sunday)
  final int weekday;
  final int hour;
  final int minute;
  final String title;
  final String body;

  int get id => 100 + weekday;

  @override
  List<Object?> get props => [weekday, hour, minute, title, body];
}
