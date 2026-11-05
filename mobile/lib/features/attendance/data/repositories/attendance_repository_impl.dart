import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/attendance.dart';
import '../../domain/repositories/attendance_repository.dart';

class AttendanceRepositoryImpl implements AttendanceRepository {
  const AttendanceRepositoryImpl(this._api);
  final ApiClient _api;

  static Attendance fromJson(Map<String, dynamic> j) => Attendance(
        id: (j['id'] as num).toInt(),
        attendedOn: Fmt.parse(j['attended_on'])!,
        checkedInAt: Fmt.parse(j['checked_in_at'])!,
        method: j['method'] as String? ?? 'manual',
        plan: j['plan'] as String?,
      );

  @override
  Future<Result<Paginated<Attendance>>> getAttendances({int page = 1}) =>
      guard(() async => Paginated.fromJson(await _api.get('/member/attendances', query: {'page': page, 'per_page': 50}), fromJson));
}
