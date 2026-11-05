import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../../../core/utils/formatters.dart';
import '../../domain/entities/check_in_result.dart';
import '../../domain/repositories/check_in_repository.dart';

class CheckInRepositoryImpl implements CheckInRepository {
  const CheckInRepositoryImpl(this._api);
  final ApiClient _api;

  @override
  Future<Result<CheckInResult>> checkIn(String code) => guard(() async {
        final json = await _api.post('/member/check-in', data: {'code': code});
        final data = json['data'] as Map<String, dynamic>;
        return CheckInResult(
          checkedInAt: Fmt.parse(data['checked_in_at']) ?? DateTime.now(),
          plan: data['plan'] as String?,
          sessionsCount: (data['sessions_count'] as num?)?.toInt(),
          sessionsUsed: (data['sessions_used'] as num?)?.toInt(),
          warning: json['warning'] as String?,
          message: json['message'] as String? ?? 'Présence enregistrée.',
        );
      });
}
