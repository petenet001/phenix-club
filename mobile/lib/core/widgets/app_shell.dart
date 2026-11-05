import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';

/// Navigation principale : 4 onglets + bouton central « Pointer ».
class AppShell extends StatelessWidget {
  const AppShell({super.key, required this.shell});

  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: shell,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: FloatingActionButton.large(
        key: const Key('scan_fab'),
        heroTag: 'scan',
        onPressed: () => context.push('/scan'),
        backgroundColor: context.colors.brand,
        foregroundColor: Colors.white,
        shape: const CircleBorder(),
        tooltip: 'Pointer ma présence',
        child: const Icon(Icons.qr_code_scanner_rounded, size: 36),
      ),
      bottomNavigationBar: BottomAppBar(
        color: context.colors.surface,
        shape: const CircularNotchedRectangle(),
        notchMargin: 8,
        padding: EdgeInsets.zero,
        height: 68,
        child: Row(
          children: [
            _Tab(
              icon: Icons.home_rounded,
              label: 'Accueil',
              index: 0,
              shell: shell,
            ),
            _Tab(
              icon: Icons.account_balance_wallet_rounded,
              label: 'Paiements',
              index: 1,
              shell: shell,
            ),
            const SizedBox(width: 96),
            _Tab(
              icon: Icons.calendar_month_rounded,
              label: 'Présences',
              index: 2,
              shell: shell,
            ),
            _Tab(
              icon: Icons.person_rounded,
              label: 'Profil',
              index: 3,
              shell: shell,
            ),
          ],
        ),
      ),
    );
  }
}

class _Tab extends StatelessWidget {
  const _Tab({
    required this.icon,
    required this.label,
    required this.index,
    required this.shell,
  });

  final IconData icon;
  final String label;
  final int index;
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    final selected = shell.currentIndex == index;
    final color = selected ? context.colors.brand : context.colors.textMuted;
    return Expanded(
      child: InkWell(
        onTap: () =>
            shell.goBranch(index, initialLocation: index == shell.currentIndex),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedScale(
              scale: selected ? 1.15 : 1,
              duration: const Duration(milliseconds: 200),
              child: Icon(icon, color: color),
            ),
            const SizedBox(height: 4),
            AnimatedDefaultTextStyle(
              duration: const Duration(milliseconds: 200),
              style: TextStyle(
                fontSize: 11,
                color: color,
                fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
              ),
              child: Text(label),
            ),
          ],
        ),
      ),
    );
  }
}
