import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/entities/program.dart';
import '../../domain/usecases/get_programs.dart';

sealed class ProgramsEvent extends Equatable {
  const ProgramsEvent();
  @override
  List<Object?> get props => [];
}

class ProgramsRequested extends ProgramsEvent {
  const ProgramsRequested();
}

sealed class ProgramsState extends Equatable {
  const ProgramsState();
  @override
  List<Object?> get props => [];
}

class ProgramsLoading extends ProgramsState {
  const ProgramsLoading();
}

class ProgramsLoaded extends ProgramsState {
  const ProgramsLoaded(this.programs);
  final List<Program> programs;
  @override
  List<Object?> get props => [programs];
}

class ProgramsFailure extends ProgramsState {
  const ProgramsFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class ProgramsBloc extends Bloc<ProgramsEvent, ProgramsState> {
  ProgramsBloc(GetProgramsUseCase getPrograms) : super(const ProgramsLoading()) {
    on<ProgramsRequested>((event, emit) async {
      emit(const ProgramsLoading());
      final result = await getPrograms();
      emit(result.fold((f) => ProgramsFailure(f.message), ProgramsLoaded.new));
    });
  }
}
