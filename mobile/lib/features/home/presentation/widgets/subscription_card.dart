import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/status_colors.dart';
import '../../../../core/widgets/ui.dart';
import '../../domain/entities/dashboard.dart';

/// Abonnement en cours : jours ou séances restants, échéance, reste dû.
class SubscriptionCard extends StatelessWidget {
  const SubscriptionCard({
    super.key,
    required this.subscription,
    this.upcoming,
  });

  final SubscriptionInfo? subscription;
  final SubscriptionInfo? upcoming;

  @override
  Widget build(BuildContext context) {
    final s = subscription;
    if (s == null) {
      return SectionCard(
        title: 'MON ABONNEMENT',
        child: Text(
          'Aucun abonnement. Passez à l\'accueil pour choisir une formule.',
          style: TextStyle(color: context.colors.textMuted),
        ),
      );
    }

    final expired = s.status == 'expired';
    final color = expired
        ? context.colors.danger
        : (s.daysLeft <= 3 && !s.isSessionCard
              ? context.colors.warning
              : context.colors.brand);

    return SectionCard(
      title: 'MON ABONNEMENT',
      trailing: StatusPill(
        label: s.statusLabel,
        color: subscriptionStatusColor(s.status, context.colors),
      ),
      child: Row(
        children: [
          SizedBox(
            width: 92,
            height: 92,
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: 1 - s.progress),
              duration: const Duration(milliseconds: 900),
              curve: Curves.easeOutCubic,
              builder: (context, value, _) => Stack(
                fit: StackFit.expand,
                children: [
                  CircularProgressIndicator(
                    value: value,
                    strokeWidth: 7,
                    backgroundColor: context.colors.surfaceHigh,
                    color: color,
                    strokeCap: StrokeCap.round,
                  ),
                  Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          s.isSessionCard
                              ? '${s.sessionsLeft}'
                              : '${s.daysLeft}',
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          s.isSessionCard ? 'séances' : 'jours',
                          style: TextStyle(
                            fontSize: 11,
                            color: context.colors.textMuted,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 18),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  s.plan,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Du ${Fmt.date(s.startDate)} au ${Fmt.date(s.endDate)}',
                  style: TextStyle(
                    color: context.colors.textMuted,
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  s.balanceDue > 0
                      ? 'Reste dû : ${Fmt.fcfa(s.balanceDue)}'
                      : 'Soldé',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: s.balanceDue > 0
                        ? context.colors.brand
                        : context.colors.success,
                  ),
                ),
                if (upcoming != null) ...[
                  const SizedBox(height: 6),
                  Text(
                    'Renouvelé : ${upcoming!.plan} dès le ${Fmt.date(upcoming!.startDate)}',
                    style: TextStyle(
                      fontSize: 12,
                      color: context.colors.textMuted,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
