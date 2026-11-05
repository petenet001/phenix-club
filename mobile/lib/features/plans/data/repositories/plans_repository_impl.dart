import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../domain/entities/plan.dart';
import '../../domain/repositories/plans_repository.dart';

class PlansRepositoryImpl implements PlansRepository {
  const PlansRepositoryImpl(this._api);
  final ApiClient _api;

  @override
  Future<Result<List<Plan>>> getPlans() => guard(() async {
        final json = await _api.get('/member/plans');
        return ((json['data'] as List?) ?? const []).cast<Map<String, dynamic>>().map((j) => Plan(
              id: (j['id'] as num).toInt(),
              name: j['name'] as String? ?? '',
              price: (j['price'] as num?)?.toInt() ?? 0,
              durationDays: (j['duration_days'] as num?)?.toInt() ?? 0,
              sessionsCount: (j['sessions_count'] as num?)?.toInt(),
              description: j['description'] as String?,
            )).toList();
      });
}
