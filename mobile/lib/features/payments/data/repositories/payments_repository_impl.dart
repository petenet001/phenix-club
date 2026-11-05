import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/payment.dart';
import '../../domain/repositories/payments_repository.dart';
import '../models/payment_models.dart';

class PaymentsRepositoryImpl implements PaymentsRepository {
  const PaymentsRepositoryImpl(this._api);
  final ApiClient _api;

  @override
  Future<Result<Balance>> getBalance() => guard(() async => PaymentModels.balance((await _api.get('/member/balance'))['data'] as Map<String, dynamic>));

  @override
  Future<Result<Paginated<Payment>>> getPayments({int page = 1}) =>
      guard(() async => Paginated.fromJson(await _api.get('/member/payments', query: {'page': page}), PaymentModels.payment));

  @override
  Future<Result<Receipt>> getReceipt(int paymentId) =>
      guard(() async => PaymentModels.receipt((await _api.get('/member/payments/$paymentId/receipt'))['data'] as Map<String, dynamic>));
}
