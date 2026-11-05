import '../../../programs/domain/entities/program.dart';
import '../entities/reminder_settings.dart';

/// Calcule les rappels de la semaine à partir du programme d'entraînement et des préférences.
/// Pure (sans Flutter ni plugin) : testable unitairement.
class ReminderPlanner {
  const ReminderPlanner();

  static const dayNames = {1: 'lundi', 2: 'mardi', 3: 'mercredi', 4: 'jeudi', 5: 'vendredi', 6: 'samedi', 7: 'dimanche'};

  /// "Lundi", "mercredi ", "Vendredi" -> 1, 3, 5 (accents et casse ignorés).
  static int? weekdayOf(String name) {
    final n = name.trim().toLowerCase().replaceAll('é', 'e').replaceAll('è', 'e');
    for (final e in dayNames.entries) {
      if (e.value.replaceAll('é', 'e') == n) return e.key;
    }
    return null;
  }

  /// Programme d'entraînement en vigueur (validé ou imposé), le plus récent.
  Program? activeTraining(List<Program> programs) {
    for (final p in programs) {
      if (p.isTraining && p.isEffective) return p;
    }
    return null;
  }

  /// Jours de séance du programme : `content.days`, sinon les jours des séances.
  Set<int> programDays(Program? program) {
    if (program == null) return {};
    final c = program.content;
    final raw = <Object?>[
      if (c['days'] is List) ...(c['days'] as List),
      if (c['sessions'] is List) ...(c['sessions'] as List).whereType<Map>().map((s) => s['day']),
    ];
    return raw.whereType<String>().map(weekdayOf).whereType<int>().toSet();
  }

  Set<int> effectiveDays(ReminderSettings settings, Program? program) => settings.customDays ?? programDays(program);

  List<SessionReminder> plan(ReminderSettings settings, Program? program) {
    if (!settings.enabled) return const [];
    final days = effectiveDays(settings, program).toList()..sort();
    return [
      for (final day in days)
        SessionReminder(
          weekday: day,
          hour: settings.hour,
          minute: settings.minute,
          title: 'Séance du ${dayNames[day]}',
          body: _body(program, day),
        ),
    ];
  }

  String _body(Program? program, int day) {
    if (program != null) {
      final sessions = program.content['sessions'];
      if (sessions is List) {
        for (final s in sessions.whereType<Map>()) {
          if (s['day'] is String && weekdayOf(s['day'] as String) == day && s['exercises'] is List) {
            final names = (s['exercises'] as List).whereType<Map>().map((e) => e['name']).whereType<String>().take(3).toList();
            if (names.isNotEmpty) return 'Au programme : ${names.join(', ')}. Bonne séance !';
          }
        }
      }
      return 'Votre programme « ${program.title} » vous attend. Bonne séance !';
    }
    return 'C\'est l\'heure de votre séance chez Phenix Club. Bonne séance !';
  }
}
