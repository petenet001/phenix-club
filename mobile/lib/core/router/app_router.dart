import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../features/attendance/presentation/pages/attendance_page.dart';
import '../../features/auth/presentation/bloc/auth_bloc.dart';
import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/checkin/presentation/pages/scan_page.dart';
import '../../features/home/presentation/pages/home_page.dart';
import '../../features/payments/presentation/pages/payments_page.dart';
import '../../features/payments/presentation/pages/receipt_page.dart';
import '../../features/plans/presentation/pages/plans_page.dart';
import '../../features/profile/presentation/pages/change_password_page.dart';
import '../../features/profile/presentation/pages/profile_page.dart';
import '../../features/programs/domain/entities/program.dart';
import '../../features/programs/presentation/pages/program_detail_page.dart';
import '../../features/programs/presentation/pages/programs_page.dart';
import '../widgets/app_shell.dart';

/// Routes de l'application ; la redirection suit l'état de l'AuthBloc.
class AppRouter {
  AppRouter(this._auth);

  final AuthBloc _auth;

  late final GoRouter router = GoRouter(
    // /login : aucun appel réseau avant de connaître la session (redirigé vers /home si connecté)
    initialLocation: '/login',
    refreshListenable: _StreamListenable(_auth.stream),
    redirect: (context, state) {
      final status = _auth.state.status;
      final location = state.matchedLocation;
      // Session en cours de vérification : l'écran de démarrage natif est encore affiché
      if (status == AuthStatus.unknown) return null;
      if (status == AuthStatus.unauthenticated) return location == '/login' ? null : '/login';
      if (location == '/login') return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, _) => const LoginPage()),
      StatefulShellRoute.indexedStack(
        builder: (context, state, shell) => AppShell(shell: shell),
        branches: [
          StatefulShellBranch(routes: [GoRoute(path: '/home', builder: (_, _) => const HomePage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/payments', builder: (_, _) => const PaymentsPage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/attendance', builder: (_, _) => const AttendancePage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/profile', builder: (_, _) => const ProfilePage())]),
        ],
      ),
      GoRoute(path: '/scan', pageBuilder: (_, _) => const MaterialPage(fullscreenDialog: true, child: ScanPage())),
      GoRoute(path: '/receipt/:id', builder: (_, state) => ReceiptPage(paymentId: int.parse(state.pathParameters['id']!))),
      GoRoute(path: '/programs', builder: (_, _) => const ProgramsPage()),
      GoRoute(path: '/program', builder: (_, state) => ProgramDetailPage(program: state.extra! as Program)),
      GoRoute(path: '/plans', builder: (_, _) => const PlansPage()),
      GoRoute(path: '/password', builder: (_, _) => const ChangePasswordPage()),
    ],
  );
}

class _StreamListenable extends ChangeNotifier {
  _StreamListenable(Stream<dynamic> stream) {
    _sub = stream.listen((_) => notifyListeners());
  }

  late final StreamSubscription<dynamic> _sub;

  @override
  void dispose() {
    _sub.cancel();
    super.dispose();
  }
}
