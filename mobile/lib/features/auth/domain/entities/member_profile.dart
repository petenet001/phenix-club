import 'package:equatable/equatable.dart';

class MemberProfile extends Equatable {
  const MemberProfile({
    required this.id,
    required this.code,
    required this.firstName,
    required this.lastName,
    required this.fullName,
    required this.phone,
    this.email,
    this.address,
    this.birthDate,
    this.photoUrl,
    required this.status,
    required this.statusLabel,
    required this.joinedAt,
    required this.qrToken,
  });

  final int id;
  final String code;
  final String firstName;
  final String lastName;
  final String fullName;
  final String phone;
  final String? email;
  final String? address;
  final DateTime? birthDate;
  final String? photoUrl;

  /// active | inactive | pending | suspended
  final String status;
  final String statusLabel;
  final DateTime joinedAt;

  /// Code personnel (QR de la carte) à présenter à l'accueil si besoin.
  final String qrToken;

  String get initials => '${firstName.isNotEmpty ? firstName[0] : ''}${lastName.isNotEmpty ? lastName[0] : ''}'.toUpperCase();

  @override
  List<Object?> get props => [id, code, fullName, phone, status, qrToken];
}
