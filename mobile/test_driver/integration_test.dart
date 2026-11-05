import 'dart:io';

import 'package:integration_test/integration_test_driver_extended.dart';

/// Enregistre les captures prises par integration_test/ dans build/screenshots/.
Future<void> main() => integrationDriver(
      onScreenshot: (name, bytes, [args]) async {
        final file = File('build/screenshots/$name.png');
        await file.create(recursive: true);
        await file.writeAsBytes(bytes);
        return true;
      },
    );
