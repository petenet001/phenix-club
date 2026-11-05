import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:phenix_client/app.dart';
import 'package:phenix_client/core/di/injection.dart';
import 'package:phenix_client/features/reminders/domain/entities/reminder_settings.dart';
import 'package:phenix_client/features/reminders/domain/repositories/reminders_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Le simulateur ne permet pas d'accorder l'autorisation de notifications sans interaction : la dernière
/// couche (plugin iOS) est remplacée par un enregistreur. Tout le reste est réel : connexion, API des
/// programmes, calcul des jours, branchement connexion / déconnexion.
class _RecordingScheduler implements NotificationScheduler {
  List<SessionReminder> scheduled = [];
  @override
  Future<bool> requestPermission() async => true;
  @override
  Future<void> scheduleWeekly(List<SessionReminder> reminders) async => scheduled = reminders;
  @override
  Future<void> cancelAll() async => scheduled = [];
  @override
  Future<void> showNow({required String title, required String body}) async {}
  @override
  Future<int> pendingCount() async => scheduled.length;
}

/// Rappels de séance avec un adhérent dont le programme prévoit lundi, mercredi et vendredi (Alain, démo).
void main() {
  final binding = IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  Future<void> settle(WidgetTester tester, [int ms = 1500]) async {
    for (var i = 0; i < ms ~/ 100; i++) {
      await tester.pump(const Duration(milliseconds: 100));
    }
  }

  testWidgets('les rappels suivent les jours du programme', (tester) async {
    await initializeDateFormatting('fr_FR');
    await const FlutterSecureStorage().deleteAll();
    // Rappels déjà activés à 17h30 : la connexion doit les programmer sans autre action
    SharedPreferences.setMockInitialValues({'reminders_enabled': true, 'reminders_hour': 17, 'reminders_minute': 30});
    await configureDependencies();
    final recorder = _RecordingScheduler();
    sl
      ..unregister<NotificationScheduler>()
      ..registerSingleton<NotificationScheduler>(recorder);
    await tester.pumpWidget(const PhenixApp());
    await binding.convertFlutterSurfaceToImage();
    await settle(tester);

    await tester.enterText(find.byKey(const Key('login_phone')), '061000001');
    await tester.enterText(find.byKey(const Key('login_password')), '123456');
    await tester.tap(find.byKey(const Key('login_submit')));
    await settle(tester, 3500);

    expect(recorder.scheduled.map((r) => r.weekday), [1, 3, 5]);
    expect(recorder.scheduled.first.title, 'Séance du lundi');
    expect(recorder.scheduled.first.body, contains('Squat'));
    expect(recorder.scheduled.every((r) => r.hour == 17 && r.minute == 30), isTrue);

    await tester.tap(find.text('Profil'));
    await settle(tester, 2500);
    await tester.scrollUntilVisible(find.text('RAPPELS DE SÉANCE'), 200, scrollable: find.byType(Scrollable).first);
    await settle(tester, 800);
    expect(find.textContaining('17h30'), findsWidgets);
    expect(find.textContaining('Remise en forme'), findsOneWidget);
    await binding.takeScreenshot('rappels');

    // Déconnexion : plus aucun rappel sur le téléphone
    await tester.scrollUntilVisible(find.text('Se déconnecter'), 200, scrollable: find.byType(Scrollable).first);
    // Sortir le bouton de sous la barre de navigation du bas
    await tester.drag(find.byType(Scrollable).first, const Offset(0, -400));
    await settle(tester, 600);
    await tester.tap(find.text('Se déconnecter'));
    await settle(tester, 600);
    await tester.tap(find.text('Déconnexion'));
    await settle(tester, 2000);
    expect(recorder.scheduled, isEmpty);
  });
}
