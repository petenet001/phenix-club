import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui.dart';
import '../bloc/programs_bloc.dart';

class ProgramsPage extends StatelessWidget {
  const ProgramsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<ProgramsBloc>()..add(const ProgramsRequested()),
      child: Scaffold(
        appBar: AppBar(title: const Text('Mes programmes')),
        body: BlocBuilder<ProgramsBloc, ProgramsState>(
          builder: (context, state) => switch (state) {
            ProgramsLoading() => const LoadingView(),
            ProgramsFailure(:final message) => ErrorView(message: message, onRetry: () => context.read<ProgramsBloc>().add(const ProgramsRequested())),
            ProgramsLoaded(:final programs) when programs.isEmpty => const EmptyView(
                icon: Icons.fitness_center_rounded,
                message: 'Aucun programme pour le moment.\nDemandez à votre coach de vous en préparer un.',
              ),
            ProgramsLoaded(:final programs) => ListView.separated(
                padding: const EdgeInsets.all(20),
                itemCount: programs.length,
                separatorBuilder: (_, _) => const SizedBox(height: 12),
                itemBuilder: (context, i) {
                  final p = programs[i];
                  return FadeSlideIn(
                    delay: Duration(milliseconds: 60 * i),
                    child: Card(
                      clipBehavior: Clip.antiAlias,
                      child: ListTile(
                        onTap: () => context.push('/program', extra: p),
                        contentPadding: const EdgeInsets.all(16),
                        leading: CircleAvatar(
                          backgroundColor: context.colors.brand.withValues(alpha: .15),
                          child: Icon(p.isTraining ? Icons.fitness_center_rounded : Icons.restaurant_rounded, color: context.colors.brand),
                        ),
                        title: Text(p.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Row(children: [
                            StatusPill(label: p.kindLabel, color: context.colors.textMuted),
                            const SizedBox(width: 6),
                            StatusPill(label: p.isEffective ? 'En vigueur' : p.statusLabel, color: p.isEffective ? context.colors.success : context.colors.warning),
                          ]),
                        ),
                        trailing: const Icon(Icons.chevron_right_rounded),
                      ),
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
