import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../bloc/theme_bloc.dart';

/// Choix Système / Clair / Sombre.
class ThemeSelector extends StatelessWidget {
  const ThemeSelector({super.key});

  @override
  Widget build(BuildContext context) {
    final mode = context.watch<ThemeBloc>().state;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: double.infinity,
          child: SegmentedButton<ThemeMode>(
            showSelectedIcon: false,
            segments: const [
              ButtonSegment(
                value: ThemeMode.system,
                label: Text('Auto', maxLines: 1),
              ),
              ButtonSegment(
                value: ThemeMode.light,
                label: Text('Clair', maxLines: 1),
              ),
              ButtonSegment(
                value: ThemeMode.dark,
                label: Text('Sombre', maxLines: 1),
              ),
            ],
            selected: {mode},
            onSelectionChanged: (s) =>
                context.read<ThemeBloc>().add(ThemeModeChanged(s.first)),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          mode == ThemeMode.system
              ? 'Suit le réglage clair / sombre du téléphone.'
              : 'Thème ${mode == ThemeMode.light ? 'clair' : 'sombre'} appliqué quel que soit le réglage du téléphone.',
          style: Theme.of(context).textTheme.bodySmall
              ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
        ),
      ],
    );
  }
}
