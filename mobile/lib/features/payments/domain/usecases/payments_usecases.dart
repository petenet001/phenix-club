import '../../../../core/result/result.dart';
import '../../../../core/utils/paginated.dart';
import '../entities/payment.dart';
import '../repositories/payments_repository.dart';

class GetBalanceUseCase {
  const GetBalanceUseCase(this._repository);
  final PaymentsRepository _repository;
  Future<Result<Balance>> call() => _repository.getBalance();
}

class GetPaymentsUseCase {
  const GetPaymentsUseCase(this._repository);
  final PaymentsRepository _repository;
  Future<Result<Paginated<Payment>>> call({int page = 1}) => _repository.getPayments(page: page);
}

class GetReceiptUseCase {
  const GetReceiptUseCase(this._repository);
  final PaymentsRepository _repository;
  Future<Result<Receipt>> call(int paymentId) => _repository.getReceipt(paymentId);
}
