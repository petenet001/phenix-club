import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:phenix_client/core/error/failures.dart';
import 'package:phenix_client/core/result/result.dart';
import 'package:phenix_client/core/utils/paginated.dart';
import 'package:phenix_client/features/payments/data/models/payment_models.dart';
import 'package:phenix_client/features/payments/domain/entities/payment.dart';
import 'package:phenix_client/features/payments/domain/repositories/payments_repository.dart';
import 'package:phenix_client/features/payments/domain/usecases/payments_usecases.dart';
import 'package:phenix_client/features/payments/presentation/bloc/payments_bloc.dart';

class _Repo extends Mock implements PaymentsRepository {}

Payment _payment(int id) => Payment(id: id, receiptNumber: 'RC-$id', paidAt: DateTime(2026, 11, id), amount: 15000, type: 'subscription', typeLabel: 'Abonnement', methodLabel: 'Espèces', cancelled: false);

void main() {
  late _Repo repo;
  const balance = Balance(totalDue: 5000, items: [], isLate: false, daysLate: 0, lateAmount: 0);

  setUp(() => repo = _Repo());
  PaymentsBloc build() => PaymentsBloc(GetBalanceUseCase(repo), GetPaymentsUseCase(repo));

  blocTest<PaymentsBloc, PaymentsState>(
    'charge le solde et la première page',
    setUp: () {
      when(() => repo.getBalance()).thenAnswer((_) async => const Success(balance));
      when(() => repo.getPayments(page: 1)).thenAnswer((_) async => Success(Paginated(items: [_payment(1)], currentPage: 1, lastPage: 2)));
    },
    build: build,
    act: (bloc) => bloc.add(const PaymentsRequested()),
    expect: () => [
      PaymentsState(status: PaymentsStatus.loaded, balance: balance, payments: [_payment(1)], page: 1, hasMore: true),
    ],
  );

  blocTest<PaymentsBloc, PaymentsState>(
    'ajoute la page suivante',
    setUp: () => when(() => repo.getPayments(page: 2)).thenAnswer((_) async => Success(Paginated(items: [_payment(2)], currentPage: 2, lastPage: 2))),
    build: build,
    seed: () => PaymentsState(status: PaymentsStatus.loaded, balance: balance, payments: [_payment(1)], page: 1, hasMore: true),
    act: (bloc) => bloc.add(const PaymentsNextPageRequested()),
    skip: 1,
    expect: () => [PaymentsState(status: PaymentsStatus.loaded, balance: balance, payments: [_payment(1), _payment(2)], page: 2, hasMore: false)],
  );

  blocTest<PaymentsBloc, PaymentsState>(
    'affiche une erreur si le chargement échoue',
    setUp: () {
      when(() => repo.getBalance()).thenAnswer((_) async => const Err(NetworkFailure()));
      when(() => repo.getPayments(page: 1)).thenAnswer((_) async => Success(Paginated(items: const [], currentPage: 1, lastPage: 1)));
    },
    build: build,
    act: (bloc) => bloc.add(const PaymentsRequested()),
    expect: () => [isA<PaymentsState>().having((s) => s.status, 'status', PaymentsStatus.failure)],
  );

  test('lit le solde renvoyé par l\'API', () {
    final b = PaymentModels.balance({
      'total_due': 20000,
      'items': [
        {'type': 'registration', 'label': 'Frais d\'inscription', 'amount': 5000},
        {'type': 'subscription', 'label': 'Solde Trimestriel', 'amount': 15000, 'period_start': '2026-10-12', 'period_end': '2027-01-09', 'price': 40000, 'amount_paid': 25000},
      ],
      'late': {'is_late': true, 'days_late': 10, 'amount_due': 15000, 'message': 'Paiement en retard'},
    });
    expect(b.totalDue, 20000);
    expect(b.items[1].amountPaid, 25000);
    expect(b.isLate, isTrue);
    expect(b.isClear, isFalse);
  });
}
