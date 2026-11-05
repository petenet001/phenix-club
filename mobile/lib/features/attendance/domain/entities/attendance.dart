import 'package:equatable/equatable.dart';

class Attendance extends Equatable {
  const Attendance({required this.id, required this.attendedOn, required this.checkedInAt, required this.method, this.plan});

  final int id;
  final DateTime attendedOn;
  final DateTime checkedInAt;

  /// app | qr | manual
  final String method;
  final String? plan;

  String get methodLabel => switch (method) { 'app' => 'Application', 'qr' => 'Carte QR', _ => 'Accueil' };

  @override
  List<Object?> get props => [id, checkedInAt, method];
}
