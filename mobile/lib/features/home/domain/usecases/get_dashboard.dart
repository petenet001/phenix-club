import '../../../../core/result/result.dart';
import '../entities/dashboard.dart';
import '../repositories/home_repository.dart';

class GetDashboardUseCase {
  const GetDashboardUseCase(this._repository);
  final HomeRepository _repository;

  Future<Result<Dashboard>> call() => _repository.getDashboard();
}
