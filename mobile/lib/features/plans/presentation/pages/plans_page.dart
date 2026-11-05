import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui.dart';
import '../bloc/plans_bloc.dart';

class PlansPage extends StatelessWidget {
  const PlansPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<PlansBloc>()..add(const PlansRequested()),
      child: Scaffold(
        appBar: AppBar(title: const Text('Nos formules')),
        body: BlocBuilder<PlansBloc, PlansState>(
          builder: (context, state) => switch (state) {
            PlansLoading() => const LoadingView(),
            PlansFailure(:final message) => ErrorView(message: message, onRetry: () => context.read<PlansBloc>().add(const PlansRequested())),
            PlansLoaded(:final plans) => ListView.separated(
                padding: const EdgeInsets.all(20),
                itemCount: plans.length + 1,
                separatorBuilder: (_, _) => const SizedBox(height: 12),
                itemBuilder: (context, i) {
                  if (i == plans.length) {
                    return Padding(
                      padding: EdgeInsets.only(top: 8),
                      child: Text('Souscription et renouvellement à l\'accueil.', textAlign: TextAlign.center, style: TextStyle(color: context.colors.textMuted)),
                    );
                  }
                  final p = plans[i];
                  return FadeSlideIn(
                    delay: Duration(milliseconds: 60 * i),
                    child: SectionCard(
                      child: Row(children: [
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(p.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 4),
                            Text(
                              p.description ?? (p.sessionsCount != null ? '${p.sessionsCount} séances sous ${p.durationDays} jours' : '${p.durationDays} jours en illimité'),
                              style: TextStyle(color: context.colors.textMuted),
                            ),
                          ]),
                        ),
                        Text(Fmt.fcfa(p.price), style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: context.colors.brand)),
                      ]),
                    ),
                  );
                },
              ),
          },
        ),
      ),
    );
  }
}
