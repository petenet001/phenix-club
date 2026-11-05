import '../../../../core/result/result.dart';
import '../../../../core/utils/paginated.dart';
import '../entities/attendance.dart';

abstract interface class AttendanceRepository {
  Future<Result<Paginated<Attendance>>> getAttendances({int page = 1});
}
