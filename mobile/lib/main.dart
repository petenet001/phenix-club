import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'app.dart';
import 'core/di/injection.dart';

Future<void> main() async {
  final binding = WidgetsFlutterBinding.ensureInitialized();
  // L'écran de démarrage natif reste affiché jusqu'à la vérification de la session (voir PhenixApp)
  FlutterNativeSplash.preserve(widgetsBinding: binding);
  await initializeDateFormatting('fr_FR');
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  await configureDependencies();
  runApp(const PhenixApp());
}
