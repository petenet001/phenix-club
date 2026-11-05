import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/entities/dashboard.dart';
import '../../domain/usecases/get_dashboard.dart';

sealed class HomeEvent extends Equatable {
  const HomeEvent();
  @override
  List<Object?> get props => [];
}

/// Premier chargement (affiche le chargement) ou rafraîchissement (garde l'écran affiché).
class HomeRequested extends HomeEvent {
  const HomeRequested({this.silent = false});
  final bool silent;
  @override
  List<Object?> get props => [silent];
}

sealed class HomeState extends Equatable {
  const HomeState();
  @override
  List<Object?> get props => [];
}

class HomeLoading extends HomeState {
  const HomeLoading();
}

class HomeLoaded extends HomeState {
  const HomeLoaded(this.dashboard, {this.refreshError});
  final Dashboard dashboard;

  /// Échec d'un rafraîchissement : les données précédentes restent affichées.
  final String? refreshError;
  @override
  List<Object?> get props => [dashboard, refreshError];
}

class HomeFailure extends HomeState {
  const HomeFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class HomeBloc extends Bloc<HomeEvent, HomeState> {
  HomeBloc(GetDashboardUseCase getDashboard) : super(const HomeLoading()) {
    on<HomeRequested>((event, emit) async {
      final previous = state;
      if (!event.silent || previous is! HomeLoaded) emit(const HomeLoading());
      final result = await getDashboard();
      emit(
        result.fold(
          (f) => previous is HomeLoaded && event.silent
              ? HomeLoaded(previous.dashboard, refreshError: f.message)
              : HomeFailure(f.message),
          HomeLoaded.new,
        ),
      );
    });
  }
}
