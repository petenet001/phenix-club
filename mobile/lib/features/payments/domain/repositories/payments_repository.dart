import '../../../../core/result/result.dart';
import '../../../../core/utils/paginated.dart';
import '../entities/payment.dart';

abstract interface class PaymentsRepository {
  Future<Result<Balance>> getBalance();

  Future<Result<Paginated<Payment>>> getPayments({int page = 1});

  Future<Result<Receipt>> getReceipt(int paymentId);
}
