import '../../../../core/result/result.dart';
import '../../../../core/utils/paginated.dart';
import '../entities/attendance.dart';
import '../repositories/attendance_repository.dart';

class GetAttendancesUseCase {
  const GetAttendancesUseCase(this._repository);
  final AttendanceRepository _repository;
  Future<Result<Paginated<Attendance>>> call({int page = 1}) => _repository.getAttendances(page: page);
}
