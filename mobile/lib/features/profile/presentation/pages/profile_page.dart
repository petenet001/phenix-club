import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/status_colors.dart';
import '../../../../core/widgets/ui.dart';
import '../../../auth/presentation/bloc/auth_bloc.dart';
import '../../../home/presentation/bloc/home_bloc.dart';
import '../../../reminders/presentation/widgets/reminders_card.dart';
import '../../../settings/presentation/widgets/theme_selector.dart';

/// Profil : informations, carte de membre (QR à présenter à l'accueil), réglages.
class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<HomeBloc>()..add(const HomeRequested()),
      child: Scaffold(
        appBar: AppBar(title: const Text('Mon profil')),
        body: BlocBuilder<HomeBloc, HomeState>(
          builder: (context, state) => switch (state) {
            HomeLoading() => const LoadingView(),
            HomeFailure(:final message) => ErrorView(
              message: message,
              onRetry: () =>
                  context.read<HomeBloc>().add(const HomeRequested()),
            ),
            HomeLoaded(:final dashboard) => ListView(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 120),
              children: [
                FadeSlideIn(
                  child: _MemberCard(
                    name: dashboard.profile.fullName,
                    code: dashboard.profile.code,
                    qr: dashboard.profile.qrToken,
                    status: dashboard.profile.status,
                    statusLabel: dashboard.profile.statusLabel,
                  ),
                ),
                const SizedBox(height: 14),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 80),
                  child: SectionCard(
                    title: 'MES INFORMATIONS',
                    child: Column(
                      children: [
                        InfoRow(
                          label: 'Téléphone',
                          value: dashboard.profile.phone,
                        ),
                        if (dashboard.profile.email != null)
                          InfoRow(
                            label: 'Email',
                            value: dashboard.profile.email!,
                          ),
                        if (dashboard.profile.address != null)
                          InfoRow(
                            label: 'Adresse',
                            value: dashboard.profile.address!,
                          ),
                        if (dashboard.profile.birthDate != null)
                          InfoRow(
                            label: 'Naissance',
                            value: Fmt.date(dashboard.profile.birthDate),
                          ),
                        InfoRow(
                          label: 'Membre depuis',
                          value: Fmt.date(dashboard.profile.joinedAt),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Une information à corriger ? Signalez-le à l\'accueil.',
                          style: TextStyle(
                            color: context.colors.textMuted,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                const FadeSlideIn(delay: Duration(milliseconds: 100), child: RemindersCard()),
                const SizedBox(height: 14),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 120),
                  child: SectionCard(
                    title: 'APPARENCE',
                    child: const ThemeSelector(),
                  ),
                ),
                const SizedBox(height: 14),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 160),
                  child: Card(
                    clipBehavior: Clip.antiAlias,
                    child: Column(
                      children: [
                        ListTile(
                          leading: const Icon(Icons.fitness_center_rounded),
                          title: const Text('Mes programmes'),
                          trailing: const Icon(Icons.chevron_right_rounded),
                          onTap: () => context.push('/programs'),
                        ),
                        ListTile(
                          leading: const Icon(Icons.sell_rounded),
                          title: const Text('Nos formules et tarifs'),
                          trailing: const Icon(Icons.chevron_right_rounded),
                          onTap: () => context.push('/plans'),
                        ),
                        ListTile(
                          leading: const Icon(Icons.lock_reset_rounded),
                          title: const Text('Changer mon code d\'accès'),
                          trailing: const Icon(Icons.chevron_right_rounded),
                          onTap: () => context.push('/password'),
                        ),
                        ListTile(
                          leading: Icon(
                            Icons.logout_rounded,
                            color: context.colors.danger,
                          ),
                          title: Text(
                            'Se déconnecter',
                            style: TextStyle(color: context.colors.danger),
                          ),
                          onTap: () => _confirmLogout(context),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          },
        ),
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Se déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );
    if (ok == true && context.mounted) {
      context.read<AuthBloc>().add(const AuthLogoutRequested());
    }
  }
}

/// Carte de membre avec QR personnel : sert à l'accueil si le téléphone ne peut pas scanner la borne.
class _MemberCard extends StatelessWidget {
  const _MemberCard({
    required this.name,
    required this.code,
    required this.qr,
    required this.status,
    required this.statusLabel,
  });
  final String name;
  final String code;
  final String qr;
  final String status;
  final String statusLabel;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: const Color(0xFF1F2124),
        border: Border.all(color: context.colors.border),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: Image.asset(
                        'assets/images/logo-phenix.png',
                        width: 26,
                        height: 26,
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Text(
                      'PHENIX CLUB',
                      style: TextStyle(
                        fontSize: 12,
                        letterSpacing: 2,
                        fontWeight: FontWeight.w700,
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Text(
                  name,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(code, style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 10),
                // La carte est toujours sombre : couleurs de la palette sombre, quel que soit le thème
                Builder(
                  builder: (context) {
                    final color = memberStatusColor(status, AppPalette.dark);
                    return Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: .18),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 7,
                            height: 7,
                            decoration: BoxDecoration(
                              color: color,
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            statusLabel,
                            style: TextStyle(
                              color: color,
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
            ),
            child: QrImageView(data: qr, size: 96, padding: EdgeInsets.zero),
          ),
        ],
      ),
    );
  }
}
