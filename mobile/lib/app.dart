import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';

import 'core/di/injection.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/presentation/bloc/auth_bloc.dart';
import 'features/reminders/domain/usecases/reminder_usecases.dart';
import 'features/settings/presentation/bloc/theme_bloc.dart';

class PhenixApp extends StatefulWidget {
  const PhenixApp({super.key});

  @override
  State<PhenixApp> createState() => _PhenixAppState();
}

class _PhenixAppState extends State<PhenixApp> {
  final _auth = sl<AuthBloc>()..add(const AuthCheckRequested());
  late final _router = AppRouter(_auth).router;

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider.value(value: _auth),
        BlocProvider.value(value: sl<ThemeBloc>()),
      ],
      child: BlocListener<AuthBloc, AuthState>(
        listenWhen: (previous, current) =>
            previous.status == AuthStatus.unknown &&
            current.status != AuthStatus.unknown,
        listener: (_, _) => FlutterNativeSplash.remove(),
        child: BlocListener<AuthBloc, AuthState>(
          // Rappels de séance : reprogrammés à la connexion (le programme a pu changer), annulés à la déconnexion
          listenWhen: (previous, current) =>
              previous.status != current.status &&
              current.status != AuthStatus.unknown,
          listener: (_, state) => state.status == AuthStatus.authenticated
              ? sl<SyncRemindersUseCase>()()
              : sl<CancelRemindersUseCase>()(),
          child: BlocBuilder<ThemeBloc, ThemeMode>(
            builder: (context, mode) => MaterialApp.router(
              title: 'Phenix Club',
              debugShowCheckedModeBanner: false,
              theme: AppTheme.light,
              darkTheme: AppTheme.dark,
              themeMode: mode,
              themeAnimationDuration: const Duration(milliseconds: 250),
              routerConfig: _router,
              locale: const Locale('fr', 'FR'),
              supportedLocales: const [Locale('fr', 'FR')],
              localizationsDelegates: const [
                GlobalMaterialLocalizations.delegate,
                GlobalWidgetsLocalizations.delegate,
                GlobalCupertinoLocalizations.delegate,
              ],
            ),
          ),
        ),
      ),
    );
  }
}
