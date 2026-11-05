import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/entities/payment.dart';
import '../../domain/usecases/payments_usecases.dart';

sealed class ReceiptEvent extends Equatable {
  const ReceiptEvent();
  @override
  List<Object?> get props => [];
}

class ReceiptRequested extends ReceiptEvent {
  const ReceiptRequested(this.paymentId);
  final int paymentId;
  @override
  List<Object?> get props => [paymentId];
}

sealed class ReceiptState extends Equatable {
  const ReceiptState();
  @override
  List<Object?> get props => [];
}

class ReceiptLoading extends ReceiptState {
  const ReceiptLoading();
}

class ReceiptLoaded extends ReceiptState {
  const ReceiptLoaded(this.receipt);
  final Receipt receipt;
  @override
  List<Object?> get props => [receipt];
}

class ReceiptFailure extends ReceiptState {
  const ReceiptFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class ReceiptBloc extends Bloc<ReceiptEvent, ReceiptState> {
  ReceiptBloc(GetReceiptUseCase getReceipt) : super(const ReceiptLoading()) {
    on<ReceiptRequested>((event, emit) async {
      emit(const ReceiptLoading());
      final result = await getReceipt(event.paymentId);
      emit(result.fold((f) => ReceiptFailure(f.message), ReceiptLoaded.new));
    });
  }
}
