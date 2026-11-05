import '../../../../core/result/result.dart';
import '../entities/dashboard.dart';

abstract interface class HomeRepository {
  Future<Result<Dashboard>> getDashboard();
}
