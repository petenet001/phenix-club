import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/entities/attendance.dart';
import '../../domain/usecases/get_attendances.dart';

sealed class AttendanceEvent extends Equatable {
  const AttendanceEvent();
  @override
  List<Object?> get props => [];
}

class AttendanceRequested extends AttendanceEvent {
  const AttendanceRequested();
}

class AttendanceNextPageRequested extends AttendanceEvent {
  const AttendanceNextPageRequested();
}

enum AttendanceStatus { loading, loaded, failure }

class AttendanceState extends Equatable {
  const AttendanceState({this.status = AttendanceStatus.loading, this.items = const [], this.page = 1, this.hasMore = false, this.loadingMore = false, this.message});

  final AttendanceStatus status;
  final List<Attendance> items;
  final int page;
  final bool hasMore;
  final bool loadingMore;
  final String? message;

  /// Présences regroupées par mois, du plus récent au plus ancien.
  Map<DateTime, List<Attendance>> get byMonth {
    final map = <DateTime, List<Attendance>>{};
    for (final a in items) {
      map.putIfAbsent(DateTime(a.attendedOn.year, a.attendedOn.month), () => []).add(a);
    }
    return map;
  }

  AttendanceState copyWith({AttendanceStatus? status, List<Attendance>? items, int? page, bool? hasMore, bool? loadingMore, String? message}) => AttendanceState(
        status: status ?? this.status,
        items: items ?? this.items,
        page: page ?? this.page,
        hasMore: hasMore ?? this.hasMore,
        loadingMore: loadingMore ?? this.loadingMore,
        message: message,
      );

  @override
  List<Object?> get props => [status, items, page, hasMore, loadingMore, message];
}

class AttendanceBloc extends Bloc<AttendanceEvent, AttendanceState> {
  AttendanceBloc(GetAttendancesUseCase getAttendances) : super(const AttendanceState()) {
    on<AttendanceRequested>((event, emit) async {
      if (state.status == AttendanceStatus.failure) emit(state.copyWith(status: AttendanceStatus.loading));
      final result = await getAttendances();
      emit(result.fold(
        (f) => state.copyWith(status: state.status == AttendanceStatus.loaded ? AttendanceStatus.loaded : AttendanceStatus.failure, message: f.message),
        (p) => AttendanceState(status: AttendanceStatus.loaded, items: p.items, page: p.currentPage, hasMore: p.hasMore),
      ));
    });
    on<AttendanceNextPageRequested>((event, emit) async {
      if (!state.hasMore || state.loadingMore) return;
      emit(state.copyWith(loadingMore: true));
      final result = await getAttendances(page: state.page + 1);
      emit(result.fold(
        (f) => state.copyWith(loadingMore: false, message: f.message),
        (p) => state.copyWith(items: [...state.items, ...p.items], page: p.currentPage, hasMore: p.hasMore, loadingMore: false),
      ));
    });
  }
}
