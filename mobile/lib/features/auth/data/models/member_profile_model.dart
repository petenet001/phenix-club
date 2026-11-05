import '../../../../core/utils/formatters.dart';
import '../../domain/entities/member_profile.dart';

class MemberProfileModel {
  const MemberProfileModel._();

  static MemberProfile fromJson(Map<String, dynamic> json) => MemberProfile(
        id: (json['id'] as num).toInt(),
        code: json['code'] as String,
        firstName: json['first_name'] as String? ?? '',
        lastName: json['last_name'] as String? ?? '',
        fullName: json['full_name'] as String? ?? '',
        phone: json['phone'] as String? ?? '',
        email: json['email'] as String?,
        address: json['address'] as String?,
        birthDate: Fmt.parse(json['birth_date']),
        photoUrl: json['photo_url'] as String?,
        status: json['status'] as String? ?? 'inactive',
        statusLabel: json['status_label'] as String? ?? '',
        joinedAt: Fmt.parse(json['joined_at']) ?? DateTime.now(),
        qrToken: json['qr_token'] as String? ?? '',
      );
}
