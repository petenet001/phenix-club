import '../../../../core/result/result.dart';
import '../entities/check_in_result.dart';

abstract interface class CheckInRepository {
  Future<Result<CheckInResult>> checkIn(String code);
}
