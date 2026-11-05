import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../domain/entities/dashboard.dart';
import '../../domain/repositories/home_repository.dart';
import '../models/dashboard_model.dart';

class HomeRepositoryImpl implements HomeRepository {
  const HomeRepositoryImpl(this._api);
  final ApiClient _api;

  @override
  Future<Result<Dashboard>> getDashboard() => guard(() async {
    final json = await _api.get('/member/me');
    return DashboardModel.fromJson(json['data'] as Map<String, dynamic>);
  });
}
