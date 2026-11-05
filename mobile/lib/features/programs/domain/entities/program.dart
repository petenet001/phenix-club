import 'package:equatable/equatable.dart';

class Program extends Equatable {
  const Program({
    required this.id,
    required this.kind,
    required this.kindLabel,
    required this.title,
    required this.status,
    required this.statusLabel,
    required this.isEffective,
    this.author,
    this.startsOn,
    this.endsOn,
    required this.content,
    this.reviewNote,
  });

  final int id;

  /// training | nutrition
  final String kind;
  final String kindLabel;
  final String title;
  final String status;
  final String statusLabel;
  final bool isEffective;
  final String? author;
  final DateTime? startsOn;
  final DateTime? endsOn;

  /// Contenu libre saisi par le coach : séances/exercices ou repas/conseils.
  final Map<String, dynamic> content;
  final String? reviewNote;

  bool get isTraining => kind == 'training';

  @override
  List<Object?> get props => [id, status, title];
}
