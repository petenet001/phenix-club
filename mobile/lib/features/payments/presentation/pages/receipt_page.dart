import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/entities/payment.dart';
import '../bloc/receipt_bloc.dart';

class ReceiptPage extends StatelessWidget {
  const ReceiptPage({super.key, required this.paymentId});
  final int paymentId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<ReceiptBloc>()..add(ReceiptRequested(paymentId)),
      child: Scaffold(
        appBar: AppBar(title: const Text('Reçu')),
        body: BlocBuilder<ReceiptBloc, ReceiptState>(
          builder: (context, state) => switch (state) {
            ReceiptLoading() => const LoadingView(),
            ReceiptFailure(:final message) => ErrorView(message: message, onRetry: () => context.read<ReceiptBloc>().add(ReceiptRequested(paymentId))),
            ReceiptLoaded(:final receipt) => SingleChildScrollView(padding: const EdgeInsets.all(20), child: _ReceiptPaper(receipt: receipt)),
          },
        ),
      ),
    );
  }
}

/// Reçu présenté comme un ticket papier (fond clair, comme celui imprimé à l'accueil).
class _ReceiptPaper extends StatelessWidget {
  const _ReceiptPaper({required this.receipt});
  final Receipt receipt;

  @override
  Widget build(BuildContext context) {
    final r = receipt;
    const ink = Color(0xFF0F172A);
    const muted = Color(0xFF64748B);

    Widget row(String label, String value) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 5),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            SizedBox(width: 120, child: Text(label, style: const TextStyle(color: muted))),
            Expanded(child: Text(value, style: const TextStyle(color: ink, fontWeight: FontWeight.w500))),
          ]),
        );

    return Stack(children: [
      Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: context.colors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Center(child: ClipRRect(borderRadius: BorderRadius.circular(10), child: Image.asset('assets/images/logo-phenix.png', width: 64, height: 64))),
          const SizedBox(height: 8),
          Text(r.clubName, textAlign: TextAlign.center, style: const TextStyle(color: ink, fontSize: 18, fontWeight: FontWeight.bold)),
          Text(r.clubAddress, textAlign: TextAlign.center, style: const TextStyle(color: muted, fontSize: 12)),
          const Divider(color: ink, thickness: 1.5, height: 28),
          Text('REÇU N° ${r.receiptNumber}', textAlign: TextAlign.center, style: const TextStyle(color: ink, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          row('Date', '${Fmt.date(r.paidAt)} à ${Fmt.time(r.paidAt)}'),
          row('Reçu de', '${r.memberName} (${r.memberCode})'),
          row('Objet', r.plan != null ? '${r.typeLabel} - ${r.plan}' : r.typeLabel),
          if (r.periodStart != null) row('Période', 'du ${Fmt.date(r.periodStart)} au ${Fmt.date(r.periodEnd)}'),
          row('Mode', r.reference != null ? '${r.methodLabel} - réf. ${r.reference}' : r.methodLabel),
          if (r.balanceDue > 0) row('Reste à payer', Fmt.fcfa(r.balanceDue)),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(border: Border.all(color: ink)),
            child: Text(Fmt.fcfa(r.amount), textAlign: TextAlign.center, style: const TextStyle(color: ink, fontSize: 22, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 8),
          Text('Arrêté le présent reçu à la somme de ${r.amountInWords} francs CFA', textAlign: TextAlign.center, style: const TextStyle(color: ink, fontStyle: FontStyle.italic, fontSize: 13)),
          const SizedBox(height: 16),
          Text('Encaissé par : ${r.receivedBy ?? '-'}', style: const TextStyle(color: muted, fontSize: 12)),
        ]),
      ),
      if (r.cancelled)
        Positioned.fill(
          child: IgnorePointer(
            child: Center(
              child: Transform.rotate(
                angle: -.3,
                child: Text('ANNULÉ', style: TextStyle(color: context.colors.danger.withValues(alpha: .35), fontSize: 56, fontWeight: FontWeight.w900)),
              ),
            ),
          ),
        ),
    ]);
  }
}
