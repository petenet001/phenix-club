import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui.dart';
import '../../domain/entities/attendance.dart';
import '../bloc/attendance_bloc.dart';

class AttendancePage extends StatelessWidget {
  const AttendancePage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(create: (_) => sl<AttendanceBloc>()..add(const AttendanceRequested()), child: const _AttendanceView());
  }
}

class _AttendanceView extends StatelessWidget {
  const _AttendanceView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes présences')),
      body: BlocBuilder<AttendanceBloc, AttendanceState>(
        builder: (context, state) {
          final bloc = context.read<AttendanceBloc>();
          return switch (state.status) {
            AttendanceStatus.loading => const LoadingView(),
            AttendanceStatus.failure => ErrorView(message: state.message ?? 'Erreur', onRetry: () => bloc.add(const AttendanceRequested())),
            AttendanceStatus.loaded => RefreshIndicator(
                color: context.colors.brand,
                onRefresh: () async {
                  bloc.add(const AttendanceRequested());
                  await bloc.stream.first;
                },
                child: NotificationListener<ScrollNotification>(
                  onNotification: (n) {
                    if (n.metrics.extentAfter < 300) bloc.add(const AttendanceNextPageRequested());
                    return false;
                  },
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 120),
                    children: [
                      if (state.items.isEmpty) const EmptyView(icon: Icons.event_busy_rounded, message: 'Aucune présence enregistrée.\nScannez la borne à votre arrivée !'),
                      for (final entry in state.byMonth.entries) ...[
                        FadeSlideIn(child: _MonthCard(month: entry.key, items: entry.value)),
                        const SizedBox(height: 14),
                      ],
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

/// Un mois : nombre de séances, calendrier des jours présents, liste détaillée.
class _MonthCard extends StatelessWidget {
  const _MonthCard({required this.month, required this.items});
  final DateTime month;
  final List<Attendance> items;

  @override
  Widget build(BuildContext context) {
    final days = items.map((a) => a.attendedOn.day).toSet();
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;
    final offset = DateTime(month.year, month.month).weekday - 1;

    return SectionCard(
      title: Fmt.monthYear(month).toUpperCase(),
      trailing: StatusPill(label: '${items.length} séance${items.length > 1 ? 's' : ''}', color: context.colors.brand),
      child: Column(children: [
        Row(children: [for (final d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']) Expanded(child: Center(child: Text(d, style: TextStyle(color: context.colors.textMuted, fontSize: 11))))]),
        const SizedBox(height: 6),
        GridView.count(
          crossAxisCount: 7,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 4,
          crossAxisSpacing: 4,
          children: [
            for (var i = 0; i < offset; i++) const SizedBox.shrink(),
            for (var day = 1; day <= daysInMonth; day++)
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                decoration: BoxDecoration(color: days.contains(day) ? context.colors.brand : context.colors.surfaceHigh, shape: BoxShape.circle),
                child: Center(child: Text('$day', style: TextStyle(fontSize: 12, color: days.contains(day) ? Colors.white : context.colors.textMuted, fontWeight: days.contains(day) ? FontWeight.bold : null))),
              ),
          ],
        ),
        const Divider(height: 24),
        for (final a in items)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 5),
            child: Row(children: [
              Expanded(child: Text(Fmt.dateLong(a.checkedInAt))),
              Text(Fmt.time(a.checkedInAt), style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(width: 10),
              Icon(a.method == 'app' ? Icons.phone_iphone_rounded : (a.method == 'qr' ? Icons.qr_code_rounded : Icons.person_rounded), size: 16, color: context.colors.textMuted),
            ]),
          ),
      ]),
    );
  }
}
