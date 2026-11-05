import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/ui.dart';
import '../bloc/reminders_bloc.dart';

/// Réglages des rappels de séance (Profil).
class RemindersCard extends StatelessWidget {
  const RemindersCard({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(create: (_) => sl<RemindersBloc>()..add(const RemindersStarted()), child: const _RemindersView());
  }
}

class _RemindersView extends StatelessWidget {
  const _RemindersView();

  static const _letters = {1: 'L', 2: 'M', 3: 'M', 4: 'J', 5: 'V', 6: 'S', 7: 'D'};

  @override
  Widget build(BuildContext context) {
    final c = context.colors;
    return BlocConsumer<RemindersBloc, RemindersState>(
      listenWhen: (a, b) => b.message != null && a.message != b.message,
      listener: (context, state) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.message!))),
      builder: (context, state) {
        final bloc = context.read<RemindersBloc>();
        final s = state.settings;
        final time = '${s.hour.toString().padLeft(2, '0')}h${s.minute.toString().padLeft(2, '0')}';

        return SectionCard(
          title: 'RAPPELS DE SÉANCE',
          child: state.loading
              ? const Padding(padding: EdgeInsets.all(8), child: Center(child: CircularProgressIndicator()))
              : Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Expanded(
                      child: Text(
                        s.enabled ? 'Une notification les jours de séance à $time.' : 'Recevez une notification les jours de séance.',
                        style: TextStyle(color: c.textMuted),
                      ),
                    ),
                    Switch(key: const Key('reminders_switch'), value: s.enabled, onChanged: (v) => bloc.add(RemindersToggled(v))),
                  ]),
                  AnimatedSize(
                    duration: const Duration(milliseconds: 250),
                    curve: Curves.easeOutCubic,
                    child: !s.enabled
                        ? const SizedBox(width: double.infinity)
                        : Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            const SizedBox(height: 8),
                            InkWell(
                              borderRadius: BorderRadius.circular(12),
                              onTap: () async {
                                final picked = await showTimePicker(context: context, initialTime: TimeOfDay(hour: s.hour, minute: s.minute));
                                if (picked != null) bloc.add(RemindersTimeChanged(picked.hour, picked.minute));
                              },
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                decoration: BoxDecoration(border: Border.all(color: c.border), borderRadius: BorderRadius.circular(12)),
                                child: Row(children: [
                                  Icon(Icons.schedule_rounded, color: c.textMuted, size: 20),
                                  const SizedBox(width: 10),
                                  const Expanded(child: Text('Heure du rappel')),
                                  Text(time, style: const TextStyle(fontWeight: FontWeight.w600)),
                                ]),
                              ),
                            ),
                            const SizedBox(height: 14),
                            Text('Jours', style: TextStyle(color: c.textMuted, fontSize: 13)),
                            const SizedBox(height: 8),
                            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                              for (final d in _letters.entries)
                                _DayChip(letter: d.value, selected: state.days.contains(d.key), onTap: () => bloc.add(RemindersDayToggled(d.key))),
                            ]),
                            const SizedBox(height: 10),
                            if (state.program != null && state.usesProgramDays)
                              Text('Jours du programme « ${state.program!.title} ».', style: TextStyle(color: c.textMuted, fontSize: 12))
                            else if (state.program != null && state.programDays.isNotEmpty)
                              TextButton(onPressed: () => bloc.add(const RemindersProgramDaysRestored()), child: const Text('Reprendre les jours du programme'))
                            else if (state.program == null)
                              Text('Pas encore de programme du coach : choisissez vos jours.', style: TextStyle(color: c.textMuted, fontSize: 12)),
                            if (state.days.isEmpty) Text('Aucun jour choisi : aucun rappel ne sera envoyé.', style: TextStyle(color: c.warning, fontSize: 12)),
                            const SizedBox(height: 6),
                            TextButton.icon(
                              onPressed: () => bloc.add(const RemindersTestRequested()),
                              icon: const Icon(Icons.notifications_active_outlined, size: 18),
                              label: const Text('Envoyer un rappel test'),
                            ),
                          ]),
                  ),
                ]),
        );
      },
    );
  }
}

class _DayChip extends StatelessWidget {
  const _DayChip({required this.letter, required this.selected, required this.onTap});
  final String letter;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.colors;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 38,
        height: 38,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: selected ? c.brand : Colors.transparent,
          border: Border.all(color: selected ? c.brand : c.border),
        ),
        child: Text(letter, style: TextStyle(fontWeight: FontWeight.w600, color: selected ? c.onBrand : c.textMuted)),
      ),
    );
  }
}
