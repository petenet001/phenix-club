import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/result/result.dart';
import '../../domain/entities/payment.dart';
import '../../domain/usecases/payments_usecases.dart';

sealed class PaymentsEvent extends Equatable {
  const PaymentsEvent();
  @override
  List<Object?> get props => [];
}

class PaymentsRequested extends PaymentsEvent {
  const PaymentsRequested();
}

class PaymentsNextPageRequested extends PaymentsEvent {
  const PaymentsNextPageRequested();
}

enum PaymentsStatus { loading, loaded, failure }

class PaymentsState extends Equatable {
  const PaymentsState({
    this.status = PaymentsStatus.loading,
    this.balance,
    this.payments = const [],
    this.page = 1,
    this.hasMore = false,
    this.loadingMore = false,
    this.message,
  });

  final PaymentsStatus status;
  final Balance? balance;
  final List<Payment> payments;
  final int page;
  final bool hasMore;
  final bool loadingMore;
  final String? message;

  PaymentsState copyWith({PaymentsStatus? status, Balance? balance, List<Payment>? payments, int? page, bool? hasMore, bool? loadingMore, String? message}) =>
      PaymentsState(
        status: status ?? this.status,
        balance: balance ?? this.balance,
        payments: payments ?? this.payments,
        page: page ?? this.page,
        hasMore: hasMore ?? this.hasMore,
        loadingMore: loadingMore ?? this.loadingMore,
        message: message,
      );

  @override
  List<Object?> get props => [status, balance, payments, page, hasMore, loadingMore, message];
}

/// Ce qui reste à payer + historique paginé des paiements.
class PaymentsBloc extends Bloc<PaymentsEvent, PaymentsState> {
  PaymentsBloc(this._getBalance, this._getPayments) : super(const PaymentsState()) {
    on<PaymentsRequested>(_onRequested);
    on<PaymentsNextPageRequested>(_onNextPage);
  }

  final GetBalanceUseCase _getBalance;
  final GetPaymentsUseCase _getPayments;

  Future<void> _onRequested(PaymentsRequested event, Emitter<PaymentsState> emit) async {
    if (state.status == PaymentsStatus.failure) emit(state.copyWith(status: PaymentsStatus.loading));
    // Les deux appels partent en parallèle
    final balanceCall = _getBalance();
    final pageCall = _getPayments();
    final balance = await balanceCall;
    final page = await pageCall;

    switch ((balance, page)) {
      case (Success(value: final b), Success(value: final p)):
        emit(PaymentsState(status: PaymentsStatus.loaded, balance: b, payments: p.items, page: p.currentPage, hasMore: p.hasMore));
      case (Err(:final failure), _) || (_, Err(:final failure)):
        emit(state.copyWith(status: state.status == PaymentsStatus.loaded ? PaymentsStatus.loaded : PaymentsStatus.failure, message: failure.message));
    }
  }

  Future<void> _onNextPage(PaymentsNextPageRequested event, Emitter<PaymentsState> emit) async {
    if (!state.hasMore || state.loadingMore) return;
    emit(state.copyWith(loadingMore: true));
    final result = await _getPayments(page: state.page + 1);
    emit(result.fold(
      (f) => state.copyWith(loadingMore: false, message: f.message),
      (p) => state.copyWith(payments: [...state.payments, ...p.items], page: p.currentPage, hasMore: p.hasMore, loadingMore: false),
    ));
  }
}
