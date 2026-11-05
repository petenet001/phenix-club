import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/ui.dart';
import '../../domain/entities/program.dart';

/// Détail d'un programme : séances et exercices (entraînement) ou repas et conseils (alimentation).
class ProgramDetailPage extends StatelessWidget {
  const ProgramDetailPage({super.key, required this.program});
  final Program program;

  List<Map<String, dynamic>> _list(Object? v) => v is List ? v.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList() : const [];

  @override
  Widget build(BuildContext context) {
    final p = program;
    final c = p.content;
    final sessions = _list(c['sessions']);
    final meals = _list(c['meals']);
    final days = c['days'] is List ? (c['days'] as List).join(', ') : null;

    final sections = <Widget>[
      SectionCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            StatusPill(label: p.kindLabel, color: context.colors.brand),
            const SizedBox(width: 8),
            StatusPill(label: p.isEffective ? 'En vigueur' : p.statusLabel, color: p.isEffective ? context.colors.success : context.colors.warning),
          ]),
          const SizedBox(height: 10),
          if (c['goal'] != null) InfoRow(label: 'Objectif', value: '${c['goal']}'),
          if (days != null) InfoRow(label: 'Jours', value: days),
          if (p.startsOn != null) InfoRow(label: 'Début', value: Fmt.date(p.startsOn)),
          if (p.endsOn != null) InfoRow(label: 'Fin', value: Fmt.date(p.endsOn)),
          if (p.author != null) InfoRow(label: 'Coach', value: p.author!),
          if (p.reviewNote != null) InfoRow(label: 'Note du coach', value: p.reviewNote!),
        ]),
      ),
      for (final s in sessions)
        SectionCard(
          title: '${s['day'] ?? 'Séance'}'.toUpperCase(),
          child: Column(children: [
            for (final e in _list(s['exercises']))
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(children: [
                  Icon(Icons.circle, size: 8, color: context.colors.brand),
                  const SizedBox(width: 10),
                  Expanded(child: Text('${e['name'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w500))),
                  Text([if (e['sets'] != null) '${e['sets']} x', if (e['reps'] != null) '${e['reps']}'].join(' '), style: TextStyle(color: context.colors.textMuted)),
                ]),
              ),
          ]),
        ),
      for (final m in meals)
        SectionCard(
          title: '${m['time'] ?? 'Repas'}'.toUpperCase(),
          child: Wrap(spacing: 8, runSpacing: 8, children: [
            for (final item in (m['items'] is List ? m['items'] as List : const [])) Chip(label: Text('$item'), backgroundColor: context.colors.surfaceHigh, side: BorderSide.none),
          ]),
        ),
      if (c['advice'] != null)
        SectionCard(title: 'CONSEILS', child: Text('${c['advice']}')),
    ];

    return Scaffold(
      appBar: AppBar(title: Text(p.title)),
      body: ListView.separated(
        padding: const EdgeInsets.all(20),
        itemCount: sections.length,
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (_, i) => FadeSlideIn(delay: Duration(milliseconds: 60 * i), child: sections[i]),
      ),
    );
  }
}
