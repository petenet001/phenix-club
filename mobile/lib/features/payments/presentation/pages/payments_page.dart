import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui.dart';
import '../../domain/entities/payment.dart';
import '../bloc/payments_bloc.dart';

class PaymentsPage extends StatelessWidget {
  const PaymentsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(create: (_) => sl<PaymentsBloc>()..add(const PaymentsRequested()), child: const _PaymentsView());
  }
}

class _PaymentsView extends StatelessWidget {
  const _PaymentsView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Paiements')),
      body: BlocBuilder<PaymentsBloc, PaymentsState>(
        builder: (context, state) {
          final bloc = context.read<PaymentsBloc>();
          return switch (state.status) {
            PaymentsStatus.loading => const LoadingView(),
            PaymentsStatus.failure => ErrorView(message: state.message ?? 'Erreur', onRetry: () => bloc.add(const PaymentsRequested())),
            PaymentsStatus.loaded => RefreshIndicator(
                color: context.colors.brand,
                onRefresh: () async {
                  bloc.add(const PaymentsRequested());
                  await bloc.stream.first;
                },
                child: NotificationListener<ScrollNotification>(
                  onNotification: (n) {
                    if (n.metrics.extentAfter < 300) bloc.add(const PaymentsNextPageRequested());
                    return false;
                  },
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 120),
                    children: [
                      FadeSlideIn(child: _BalanceSection(balance: state.balance!)),
                      const SizedBox(height: 24),
                      Text('HISTORIQUE', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: context.colors.textMuted, letterSpacing: .4)),
                      const SizedBox(height: 10),
                      if (state.payments.isEmpty) const EmptyView(icon: Icons.receipt_long_rounded, message: 'Aucun paiement pour le moment.'),
                      for (var i = 0; i < state.payments.length; i++)
                        FadeSlideIn(
                          delay: Duration(milliseconds: 40 * (i % 10)),
                          child: Padding(padding: const EdgeInsets.only(bottom: 10), child: _PaymentTile(payment: state.payments[i])),
                        ),
                      if (state.loadingMore) const Padding(padding: EdgeInsets.all(16), child: Center(child: CircularProgressIndicator())),
                    ],
                  ),
                ),
              ),
          };
        },
      ),
    );
  }
}

class _BalanceSection extends StatelessWidget {
  const _BalanceSection({required this.balance});
  final Balance balance;

  @override
  Widget build(BuildContext context) {
    if (balance.isClear) {
      return SectionCard(
        child: Row(children: [
          Icon(Icons.verified_rounded, color: context.colors.success, size: 32),
          SizedBox(width: 14),
          Expanded(child: Text('Vous êtes à jour, rien à régler.', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600))),
        ]),
      );
    }

    return SectionCard(
      title: 'À RÉGLER',
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (balance.totalDue > 0) Text(Fmt.fcfa(balance.totalDue), style: TextStyle(fontSize: 30, fontWeight: FontWeight.bold, color: context.colors.brand)),
        for (final item in balance.items)
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: Row(children: [
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(item.label, style: const TextStyle(fontWeight: FontWeight.w600)),
                  if (item.periodStart != null)
                    Text('Du ${Fmt.date(item.periodStart)} au ${Fmt.date(item.periodEnd)}', style: TextStyle(color: context.colors.textMuted, fontSize: 12)),
                  if (item.price != null)
                    Text('Prix ${Fmt.fcfa(item.price!)} · déjà payé ${Fmt.fcfa(item.amountPaid ?? 0)}', style: TextStyle(color: context.colors.textMuted, fontSize: 12)),
                ]),
              ),
              Text(Fmt.fcfa(item.amount), style: const TextStyle(fontWeight: FontWeight.w600)),
            ]),
          ),
        if (balance.isLate) ...[
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: context.colors.danger.withValues(alpha: .12), borderRadius: BorderRadius.circular(14)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Retard de paiement · ${balance.daysLate} jour(s)', style: TextStyle(color: context.colors.danger, fontWeight: FontWeight.bold)),
              if (balance.lateAmount > 0) Text('Montant attendu pour renouveler : ${Fmt.fcfa(balance.lateAmount)}', style: TextStyle(color: context.colors.danger)),
              if (balance.lateMessage != null) ...[const SizedBox(height: 4), Text(balance.lateMessage!, style: const TextStyle(fontSize: 13))],
            ]),
          ),
        ],
        const SizedBox(height: 12),
        Text('Paiement à l\'accueil : espèces, Mobile Money ou virement. Votre reçu apparaîtra ici.', style: TextStyle(color: context.colors.textMuted, fontSize: 12)),
      ]),
    );
  }
}

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({required this.payment});
  final Payment payment;

  @override
  Widget build(BuildContext context) {
    final p = payment;
    final icon = switch (p.type) { 'registration' => Icons.how_to_reg_rounded, 'subscription' => Icons.card_membership_rounded, _ => Icons.shopping_bag_rounded };
    return Card(
      clipBehavior: Clip.antiAlias,
      child: ListTile(
        onTap: () => context.push('/receipt/${p.id}'),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        leading: CircleAvatar(backgroundColor: context.colors.brand.withValues(alpha: .15), child: Icon(icon, color: context.colors.brand)),
        title: Text(p.plan != null ? '${p.typeLabel} · ${p.plan}' : p.typeLabel, style: TextStyle(fontWeight: FontWeight.w600, decoration: p.cancelled ? TextDecoration.lineThrough : null)),
        subtitle: Text('${Fmt.date(p.paidAt)} · ${p.methodLabel}${p.cancelled ? ' · annulé' : ''}', style: TextStyle(color: context.colors.textMuted, fontSize: 12)),
        trailing: Text(Fmt.fcfa(p.amount), style: TextStyle(fontWeight: FontWeight.bold, color: p.cancelled ? context.colors.textMuted : null)),
      ),
    );
  }
}
