import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/member_avatar.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui.dart';
import '../../domain/entities/dashboard.dart';
import '../bloc/home_bloc.dart';
import '../widgets/subscription_card.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<HomeBloc>()..add(const HomeRequested()),
      child: const _HomeView(),
    );
  }
}

class _HomeView extends StatelessWidget {
  const _HomeView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: BlocConsumer<HomeBloc, HomeState>(
          listener: (context, state) {
            if (state is HomeLoaded && state.refreshError != null) {
              ScaffoldMessenger.of(context)
                  .showSnackBar(SnackBar(content: Text(state.refreshError!)));
            }
          },
          builder: (context, state) => switch (state) {
            HomeLoading() => const LoadingView(),
            HomeFailure(:final message) => ErrorView(
              message: message,
              onRetry: () =>
                  context.read<HomeBloc>().add(const HomeRequested()),
            ),
            HomeLoaded(:final dashboard) => RefreshIndicator(
              color: context.colors.brand,
              onRefresh: () async {
                final bloc = context.read<HomeBloc>()
                  ..add(const HomeRequested(silent: true));
                await bloc.stream.firstWhere((s) => s is! HomeLoading);
              },
              child: _Content(dashboard: dashboard),
            ),
          },
        ),
      ),
    );
  }
}

class _Content extends StatelessWidget {
  const _Content({required this.dashboard});

  final Dashboard dashboard;

  Future<void> _scan(BuildContext context) async {
    final bloc = context.read<HomeBloc>();
    final checkedIn = await context.push<bool>('/scan');
    if (checkedIn == true) bloc.add(const HomeRequested(silent: true));
  }

  @override
  Widget build(BuildContext context) {
    final d = dashboard;
    final cards = <Widget>[
      _AccessCard(access: d.access, onScan: () => _scan(context)),
      SubscriptionCard(
        subscription: d.subscription,
        upcoming: d.upcomingSubscription,
      ),
      if (d.totalDue > 0 || d.isLate) _BalanceCard(dashboard: d),
      _StatsRow(dashboard: d),
      _Shortcuts(club: d.club),
    ];

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 120),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        FadeSlideIn(child: _Header(dashboard: d)),
        const SizedBox(height: 20),
        for (var i = 0; i < cards.length; i++) ...[
          FadeSlideIn(
            delay: Duration(milliseconds: 70 * (i + 1)),
            child: cards[i],
          ),
          const SizedBox(height: 14),
        ],
      ],
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.dashboard});
  final Dashboard dashboard;

  @override
  Widget build(BuildContext context) {
    final p = dashboard.profile;
    final hour = DateTime.now().hour;
    final greeting = hour < 12
        ? 'Bonjour'
        : (hour < 18 ? 'Bon après-midi' : 'Bonsoir');
    return Row(
      children: [
        MemberAvatar(
          initials: p.initials,
          photoUrl: p.photoUrl,
          onTap: () => context.go('/profile'),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '$greeting,',
                style: TextStyle(color: context.colors.textMuted),
              ),
              Text(
                p.firstName,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
        Text(
          p.code,
          style: TextStyle(color: context.colors.textMuted, fontSize: 12),
        ),
      ],
    );
  }
}

/// Peut-on entrer aujourd'hui, et bouton pour pointer.
class _AccessCard extends StatelessWidget {
  const _AccessCard({required this.access, required this.onScan});
  final AccessInfo access;
  final VoidCallback onScan;

  @override
  Widget build(BuildContext context) {
    final (color, icon, title) = access.alreadyCheckedIn
        ? (
            context.colors.success,
            Icons.check_circle_rounded,
            'Présence enregistrée',
          )
        : access.allowed
        ? (
            access.warning ? context.colors.warning : context.colors.success,
            access.warning ? Icons.error_rounded : Icons.verified_rounded,
            'Accès autorisé',
          )
        : (context.colors.danger, Icons.block_rounded, 'Accès refusé');

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: context.colors.surface,
        border: Border.all(color: context.colors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: context.colors.tint(color),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ),
              Text(
                "Aujourd'hui",
                style: TextStyle(color: context.colors.textMuted, fontSize: 12),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(access.message),
          if (access.allowed && !access.alreadyCheckedIn) ...[
            const SizedBox(height: 14),
            FilledButton.icon(
              onPressed: onScan,
              icon: const Icon(Icons.qr_code_scanner_rounded),
              label: const Text('Pointer ma présence'),
            ),
          ],
        ],
      ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.dashboard});
  final Dashboard dashboard;

  @override
  Widget build(BuildContext context) {
    final d = dashboard;
    return SectionCard(
      title: 'À RÉGLER',
      trailing: Icon(
        Icons.chevron_right_rounded,
        color: context.colors.textMuted,
      ),
      onTap: () => context.go('/payments'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (d.totalDue > 0)
            Text(
              Fmt.fcfa(d.totalDue),
              style: TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.bold,
                color: context.colors.brand,
              ),
            ),
          if (d.isLate) ...[
            const SizedBox(height: 6),
            StatusPill(
              label: 'Retard de paiement : ${d.daysLate} jour(s)',
              color: context.colors.danger,
            ),
          ],
          const SizedBox(height: 8),
          Text(
            'Réglez à l\'accueil en espèces ou Mobile Money.',
            style: TextStyle(color: context.colors.textMuted, fontSize: 13),
          ),
        ],
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.dashboard});
  final Dashboard dashboard;

  @override
  Widget build(BuildContext context) {
    final last = dashboard.lastAttendanceAt;
    return Row(
      children: [
        Expanded(
          child: _Stat(
            value: '${dashboard.attendancesThisMonth}',
            label: 'séances\nce mois',
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Stat(
            value: '${dashboard.attendancesTotal}',
            label: 'séances\nau total',
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Stat(
            value: last == null ? '-' : Fmt.date(last).substring(0, 5),
            label: 'dernière\nvisite',
          ),
        ),
      ],
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
        child: Column(
          children: [
            TweenAnimationBuilder<double>(
              tween: Tween(begin: .6, end: 1),
              duration: const Duration(milliseconds: 500),
              curve: Curves.elasticOut,
              builder: (_, s, child) => Transform.scale(scale: s, child: child),
              child: Text(
                value,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(color: context.colors.textMuted, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

class _Shortcuts extends StatelessWidget {
  const _Shortcuts({required this.club});
  final ClubInfo club;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _Shortcut(
            icon: Icons.fitness_center_rounded,
            label: 'Mon programme',
            onTap: () => context.push('/programs'),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Shortcut(
            icon: Icons.sell_rounded,
            label: 'Tarifs',
            onTap: () => context.push('/plans'),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Shortcut(
            icon: Icons.chat_rounded,
            label: 'Contacter',
            onTap: club.whatsappUrl == null && club.phone.isEmpty
                ? null
                : () => launchUrl(
                    Uri.parse(club.whatsappUrl ?? 'tel:${club.phone}'),
                    mode: LaunchMode.externalApplication,
                  ),
          ),
        ),
      ],
    );
  }
}

class _Shortcut extends StatelessWidget {
  const _Shortcut({
    required this.icon,
    required this.label,
    required this.onTap,
  });
  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Opacity(
          opacity: onTap == null ? .4 : 1,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Column(
              children: [
                Icon(icon, color: context.colors.brand),
                const SizedBox(height: 8),
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
