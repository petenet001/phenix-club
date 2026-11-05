import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:phenix_client/core/result/result.dart';
import 'package:phenix_client/features/programs/domain/entities/program.dart';
import 'package:phenix_client/features/programs/domain/repositories/programs_repository.dart';
import 'package:phenix_client/features/programs/domain/usecases/get_programs.dart';
import 'package:phenix_client/features/reminders/data/services/local_notification_scheduler.dart';
import 'package:phenix_client/features/reminders/domain/entities/reminder_settings.dart';
import 'package:phenix_client/features/reminders/domain/repositories/reminders_repository.dart';
import 'package:phenix_client/features/reminders/domain/services/reminder_planner.dart';
import 'package:phenix_client/features/reminders/domain/usecases/reminder_usecases.dart';
import 'package:phenix_client/features/reminders/presentation/bloc/reminders_bloc.dart';
import 'package:timezone/data/latest_all.dart' as tzdata;
import 'package:timezone/timezone.dart' as tz;

const _program = Program(
  id: 1,
  kind: 'training',
  kindLabel: 'Entraînement',
  title: 'Remise en forme',
  status: 'imposed',
  statusLabel: 'Imposé',
  isEffective: true,
  content: {
    'days': ['lundi', 'Mercredi', 'vendredi'],
    'sessions': [
      {'day': 'lundi', 'exercises': [{'name': 'Squat'}, {'name': 'Développé couché'}, {'name': 'Gainage'}, {'name': 'Pompes'}]},
    ],
  },
);

class _Programs extends Mock implements ProgramsRepository {}

class _Settings implements ReminderSettingsRepository {
  ReminderSettings value = const ReminderSettings();
  @override
  ReminderSettings get settings => value;
  @override
  Future<void> save(ReminderSettings s) async => value = s;
}

class _Scheduler implements NotificationScheduler {
  bool granted = true;
  List<SessionReminder> scheduled = [];
  int tests = 0;
  @override
  Future<bool> requestPermission() async => granted;
  @override
  Future<void> scheduleWeekly(List<SessionReminder> r) async => scheduled = r;
  @override
  Future<void> cancelAll() async => scheduled = [];
  @override
  Future<void> showNow({required String title, required String body}) async => tests++;
  @override
  Future<int> pendingCount() async => scheduled.length;
}

void main() {
  const planner = ReminderPlanner();

  group('ReminderPlanner', () {
    test('lit les jours du programme, accents et casse ignorés', () {
      expect(planner.programDays(_program), {1, 3, 5});
      expect(ReminderPlanner.weekdayOf(' Dimanche '), 7);
      expect(ReminderPlanner.weekdayOf('n\'importe'), isNull);
    });

    test('un rappel par jour, avec les exercices de la séance', () {
      final reminders = planner.plan(const ReminderSettings(enabled: true, hour: 18, minute: 30), _program);
      expect(reminders.map((r) => r.weekday), [1, 3, 5]);
      expect(reminders.first.title, 'Séance du lundi');
      expect(reminders.first.body, 'Au programme : Squat, Développé couché, Gainage. Bonne séance !');
      expect(reminders[1].body, contains('Remise en forme'));
      expect(reminders.first.hour, 18);
    });

    test('jours personnalisés, sans programme, ou rappels désactivés', () {
      expect(planner.plan(const ReminderSettings(enabled: true, customDays: {2, 4}), _program).map((r) => r.weekday), [2, 4]);
      expect(planner.plan(const ReminderSettings(enabled: true, customDays: {6}), null).single.body, contains('Phenix Club'));
      expect(planner.plan(const ReminderSettings(), _program), isEmpty);
    });

    test('ignore un programme non validé ou d\'alimentation', () {
      const nutrition = Program(id: 2, kind: 'nutrition', kindLabel: '', title: '', status: 'imposed', statusLabel: '', isEffective: true, content: {});
      const proposed = Program(id: 3, kind: 'training', kindLabel: '', title: '', status: 'proposed', statusLabel: '', isEffective: false, content: {});
      expect(planner.activeTraining([nutrition, proposed]), isNull);
      expect(planner.activeTraining([nutrition, _program]), _program);
    });
  });

  test('prochaine occurrence d\'un rappel hebdomadaire', () {
    tzdata.initializeTimeZones();
    final bzv = tz.getLocation('Africa/Brazzaville');
    // Mercredi 4 novembre 2026, 16h00
    final now = tz.TZDateTime(bzv, 2026, 11, 4, 16);
    expect(LocalNotificationScheduler.nextInstance(3, 17, 0, now), tz.TZDateTime(bzv, 2026, 11, 4, 17));
    expect(LocalNotificationScheduler.nextInstance(3, 15, 0, now), tz.TZDateTime(bzv, 2026, 11, 11, 15));
    expect(LocalNotificationScheduler.nextInstance(1, 17, 0, now), tz.TZDateTime(bzv, 2026, 11, 9, 17));
  });

  group('RemindersBloc', () {
    late _Settings settings;
    late _Scheduler scheduler;
    late _Programs programs;

    setUp(() {
      settings = _Settings();
      scheduler = _Scheduler();
      programs = _Programs();
      when(() => programs.getPrograms()).thenAnswer((_) async => const Success([_program]));
    });

    RemindersBloc build() => RemindersBloc(
          getSettings: GetReminderSettingsUseCase(settings),
          apply: ApplyRemindersUseCase(settings, scheduler, planner),
          requestPermission: RequestNotificationPermissionUseCase(scheduler),
          sendTest: SendTestReminderUseCase(scheduler),
          getPrograms: GetProgramsUseCase(programs),
        );

    blocTest<RemindersBloc, RemindersState>(
      'activer programme les jours du programme',
      build: build,
      act: (b) async {
        b.add(const RemindersStarted());
        await Future<void>.delayed(Duration.zero);
        b.add(const RemindersToggled(true));
      },
      verify: (b) {
        expect(b.state.settings.enabled, isTrue);
        expect(b.state.days, {1, 3, 5});
        expect(scheduler.scheduled.map((r) => r.weekday), [1, 3, 5]);
        expect(settings.value.enabled, isTrue);
      },
    );

    blocTest<RemindersBloc, RemindersState>(
      'autorisation refusée : rien n\'est programmé et un message explique quoi faire',
      setUp: () => scheduler.granted = false,
      build: build,
      act: (b) async {
        b.add(const RemindersStarted());
        await Future<void>.delayed(Duration.zero);
        b.add(const RemindersToggled(true));
      },
      verify: (b) {
        expect(b.state.settings.enabled, isFalse);
        expect(b.state.message, contains('réglages du téléphone'));
        expect(scheduler.scheduled, isEmpty);
      },
    );

    blocTest<RemindersBloc, RemindersState>(
      'changer un jour puis revenir aux jours du programme',
      setUp: () => settings.value = const ReminderSettings(enabled: true),
      build: build,
      act: (b) async {
        b.add(const RemindersStarted());
        await Future<void>.delayed(Duration.zero);
        b.add(const RemindersDayToggled(5));
        await Future<void>.delayed(Duration.zero);
        expect(scheduler.scheduled.map((r) => r.weekday), [1, 3]);
        expect(b.state.usesProgramDays, isFalse);
        b.add(const RemindersProgramDaysRestored());
      },
      verify: (b) {
        expect(b.state.usesProgramDays, isTrue);
        expect(scheduler.scheduled.map((r) => r.weekday), [1, 3, 5]);
      },
    );

    test('synchronisation à la connexion : reprogramme seulement si activé', () async {
      final sync = SyncRemindersUseCase(settings, ApplyRemindersUseCase(settings, scheduler, planner), GetProgramsUseCase(programs), planner);
      await sync();
      expect(scheduler.scheduled, isEmpty);
      settings.value = const ReminderSettings(enabled: true, hour: 7);
      await sync();
      expect(scheduler.scheduled.map((r) => r.hour).toSet(), {7});
    });
  });
}
