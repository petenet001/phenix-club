import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/entities/plan.dart';
import '../../domain/usecases/get_plans.dart';

sealed class PlansEvent extends Equatable {
  const PlansEvent();
  @override
  List<Object?> get props => [];
}

class PlansRequested extends PlansEvent {
  const PlansRequested();
}

sealed class PlansState extends Equatable {
  const PlansState();
  @override
  List<Object?> get props => [];
}

class PlansLoading extends PlansState {
  const PlansLoading();
}

class PlansLoaded extends PlansState {
  const PlansLoaded(this.plans);
  final List<Plan> plans;
  @override
  List<Object?> get props => [plans];
}

class PlansFailure extends PlansState {
  const PlansFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class PlansBloc extends Bloc<PlansEvent, PlansState> {
  PlansBloc(GetPlansUseCase getPlans) : super(const PlansLoading()) {
    on<PlansRequested>((event, emit) async {
      emit(const PlansLoading());
      final result = await getPlans();
      emit(result.fold((f) => PlansFailure(f.message), PlansLoaded.new));
    });
  }
}
