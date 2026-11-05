import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:phenix_client/app.dart';
import 'package:phenix_client/core/di/injection.dart';

/// Parcours réel contre l'API locale (php artisan serve) avec un adhérent de démonstration :
/// flutter drive --driver test_driver/integration_test.dart --target integration_test/app_test.dart
void main() {
  final binding = IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  Future<void> settle(WidgetTester tester, [int ms = 1500]) async {
    for (var i = 0; i < ms ~/ 100; i++) {
      await tester.pump(const Duration(milliseconds: 100));
    }
  }

  testWidgets('connexion, accueil, paiements, présences, profil', (tester) async {
    await initializeDateFormatting('fr_FR');
    await const FlutterSecureStorage().deleteAll();
    SharedPreferences.setMockInitialValues({});
    await configureDependencies();
    await tester.pumpWidget(const PhenixApp());
    await binding.convertFlutterSurfaceToImage();
    await settle(tester);

    await tester.enterText(find.byKey(const Key('login_phone')), '061000004');
    await tester.enterText(find.byKey(const Key('login_password')), '123456');
    await settle(tester, 500);
    await binding.takeScreenshot('1-connexion');
    await tester.tap(find.byKey(const Key('login_submit')));
    await settle(tester, 3000);

    expect(find.text('Prisca'), findsOneWidget);
    expect(find.text('Trimestriel'), findsOneWidget);
    await binding.takeScreenshot('2-accueil');

    await tester.tap(find.text('Paiements'));
    await settle(tester, 2500);
    expect(find.text('À RÉGLER'), findsOneWidget);
    await binding.takeScreenshot('3-paiements');

    await tester.tap(find.byType(ListTile).first);
    await settle(tester, 2500);
    expect(find.textContaining('REÇU N°'), findsOneWidget);
    await binding.takeScreenshot('4-recu');
    await tester.tap(find.byType(BackButton));
    await settle(tester);

    await tester.tap(find.text('Présences'));
    await settle(tester, 2500);
    await binding.takeScreenshot('5-presences');

    await tester.tap(find.text('Profil'));
    await settle(tester, 2500);
    expect(find.text('PHENIX CLUB'), findsOneWidget);
    await binding.takeScreenshot('6-profil');

    // Mode clair
    await tester.tap(find.text('Clair'));
    await settle(tester);
    await binding.takeScreenshot('7-profil-clair');

    await tester.tap(find.text('Accueil'));
    await settle(tester, 2500);
    await binding.takeScreenshot('8-accueil-clair');

    await tester.tap(find.text('Paiements'));
    await settle(tester, 2500);
    await binding.takeScreenshot('9-paiements-clair');

    // Retour au mode sombre pour l'accueil
    await tester.tap(find.text('Profil'));
    await settle(tester);
    await tester.tap(find.text('Sombre'));
    await tester.tap(find.text('Accueil'));
    await settle(tester, 2500);
    await binding.takeScreenshot('10-accueil-sombre');
  });
}
