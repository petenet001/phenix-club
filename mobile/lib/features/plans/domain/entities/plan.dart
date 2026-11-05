import 'package:equatable/equatable.dart';

class Plan extends Equatable {
  const Plan({required this.id, required this.name, required this.price, required this.durationDays, this.sessionsCount, this.description});

  final int id;
  final String name;
  final int price;
  final int durationDays;
  final int? sessionsCount;
  final String? description;

  @override
  List<Object?> get props => [id, name, price];
}
