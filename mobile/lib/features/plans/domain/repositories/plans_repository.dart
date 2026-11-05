import '../../../../core/result/result.dart';
import '../entities/plan.dart';

abstract interface class PlansRepository {
  Future<Result<List<Plan>>> getPlans();
}
