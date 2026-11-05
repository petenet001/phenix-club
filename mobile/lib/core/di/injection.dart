import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:get_it/get_it.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../features/attendance/data/repositories/attendance_repository_impl.dart';
import '../../features/attendance/domain/repositories/attendance_repository.dart';
import '../../features/attendance/domain/usecases/get_attendances.dart';
import '../../features/attendance/presentation/bloc/attendance_bloc.dart';
import '../../features/auth/data/datasources/auth_remote_data_source.dart';
import '../../features/auth/data/repositories/auth_repository_impl.dart';
import '../../features/auth/domain/repositories/auth_repository.dart';
import '../../features/auth/domain/usecases/auth_usecases.dart';
import '../../features/auth/presentation/bloc/auth_bloc.dart';
import '../../features/auth/presentation/bloc/login_bloc.dart';
import '../../features/checkin/data/repositories/check_in_repository_impl.dart';
import '../../features/checkin/domain/repositories/check_in_repository.dart';
import '../../features/checkin/domain/usecases/check_in.dart';
import '../../features/checkin/presentation/bloc/check_in_bloc.dart';
import '../../features/home/data/repositories/home_repository_impl.dart';
import '../../features/home/domain/repositories/home_repository.dart';
import '../../features/home/domain/usecases/get_dashboard.dart';
import '../../features/home/presentation/bloc/home_bloc.dart';
import '../../features/payments/data/repositories/payments_repository_impl.dart';
import '../../features/payments/domain/repositories/payments_repository.dart';
import '../../features/payments/domain/usecases/payments_usecases.dart';
import '../../features/payments/presentation/bloc/payments_bloc.dart';
import '../../features/payments/presentation/bloc/receipt_bloc.dart';
import '../../features/plans/data/repositories/plans_repository_impl.dart';
import '../../features/plans/domain/repositories/plans_repository.dart';
import '../../features/plans/domain/usecases/get_plans.dart';
import '../../features/plans/presentation/bloc/plans_bloc.dart';
import '../../features/profile/presentation/bloc/change_password_bloc.dart';
import '../../features/reminders/data/repositories/reminder_settings_repository_impl.dart';
import '../../features/reminders/data/services/local_notification_scheduler.dart';
import '../../features/reminders/domain/repositories/reminders_repository.dart';
import '../../features/reminders/domain/services/reminder_planner.dart';
import '../../features/reminders/domain/usecases/reminder_usecases.dart';
import '../../features/reminders/presentation/bloc/reminders_bloc.dart';
import '../../features/settings/data/repositories/settings_repository_impl.dart';
import '../../features/settings/domain/repositories/settings_repository.dart';
import '../../features/settings/domain/usecases/theme_usecases.dart';
import '../../features/settings/presentation/bloc/theme_bloc.dart';
import '../../features/programs/data/repositories/programs_repository_impl.dart';
import '../../features/programs/domain/repositories/programs_repository.dart';
import '../../features/programs/domain/usecases/get_programs.dart';
import '../../features/programs/presentation/bloc/programs_bloc.dart';
import '../config/app_config.dart';
import '../network/api_client.dart';
import '../network/session_events.dart';
import '../storage/token_storage.dart';

/// Localisateur de services : core -> data -> domain -> présentation.
final sl = GetIt.instance;

Future<void> configureDependencies() async {
  final prefs = await SharedPreferences.getInstance();
  final notifications = LocalNotificationScheduler();
  await notifications.init();

  // Core
  sl
    ..registerSingleton(prefs)
    ..registerSingleton<NotificationScheduler>(notifications)
    ..registerLazySingleton(() => TokenStorage(const FlutterSecureStorage()))
    ..registerLazySingleton(SessionEvents.new)
    ..registerLazySingleton(() => ApiClient(baseUrl: AppConfig.apiUrl, tokens: sl(), session: sl()));

  // Data
  sl
    ..registerLazySingleton(() => AuthRemoteDataSource(sl()))
    ..registerLazySingleton<AuthRepository>(() => AuthRepositoryImpl(sl(), sl()))
    ..registerLazySingleton<HomeRepository>(() => HomeRepositoryImpl(sl()))
    ..registerLazySingleton<PaymentsRepository>(() => PaymentsRepositoryImpl(sl()))
    ..registerLazySingleton<AttendanceRepository>(() => AttendanceRepositoryImpl(sl()))
    ..registerLazySingleton<ProgramsRepository>(() => ProgramsRepositoryImpl(sl()))
    ..registerLazySingleton<PlansRepository>(() => PlansRepositoryImpl(sl()))
    ..registerLazySingleton<CheckInRepository>(() => CheckInRepositoryImpl(sl()))
    ..registerLazySingleton<SettingsRepository>(() => SettingsRepositoryImpl(sl()))
    ..registerLazySingleton<ReminderSettingsRepository>(() => ReminderSettingsRepositoryImpl(sl()));

  // Domaine
  sl
    ..registerLazySingleton(() => LoginUseCase(sl()))
    ..registerLazySingleton(() => CheckSessionUseCase(sl()))
    ..registerLazySingleton(() => LogoutUseCase(sl()))
    ..registerLazySingleton(() => ClearSessionUseCase(sl()))
    ..registerLazySingleton(() => ChangePasswordUseCase(sl()))
    ..registerLazySingleton(() => GetDashboardUseCase(sl()))
    ..registerLazySingleton(() => GetBalanceUseCase(sl()))
    ..registerLazySingleton(() => GetPaymentsUseCase(sl()))
    ..registerLazySingleton(() => GetReceiptUseCase(sl()))
    ..registerLazySingleton(() => GetAttendancesUseCase(sl()))
    ..registerLazySingleton(() => GetProgramsUseCase(sl()))
    ..registerLazySingleton(() => GetPlansUseCase(sl()))
    ..registerLazySingleton(() => CheckInUseCase(sl()))
    ..registerLazySingleton(() => GetThemeModeUseCase(sl()))
    ..registerLazySingleton(() => SaveThemeModeUseCase(sl()))
    ..registerLazySingleton(() => const ReminderPlanner())
    ..registerLazySingleton(() => GetReminderSettingsUseCase(sl()))
    ..registerLazySingleton(() => ApplyRemindersUseCase(sl(), sl(), sl()))
    ..registerLazySingleton(() => SyncRemindersUseCase(sl(), sl(), sl(), sl()))
    ..registerLazySingleton(() => CancelRemindersUseCase(sl()))
    ..registerLazySingleton(() => RequestNotificationPermissionUseCase(sl()))
    ..registerLazySingleton(() => SendTestReminderUseCase(sl()));

  // Présentation : la session est unique, les écrans reçoivent un bloc neuf
  sl
    ..registerLazySingleton(() => AuthBloc(checkSession: sl(), logout: sl(), clearSession: sl(), session: sl()))
    ..registerLazySingleton(() => ThemeBloc(getThemeMode: sl(), saveThemeMode: sl()))
    ..registerFactory(() => LoginBloc(sl()))
    ..registerFactory(() => HomeBloc(sl()))
    ..registerFactory(() => PaymentsBloc(sl(), sl()))
    ..registerFactory(() => ReceiptBloc(sl()))
    ..registerFactory(() => AttendanceBloc(sl()))
    ..registerFactory(() => ProgramsBloc(sl()))
    ..registerFactory(() => PlansBloc(sl()))
    ..registerFactory(() => CheckInBloc(sl()))
    ..registerFactory(() => ChangePasswordBloc(sl()))
    ..registerFactory(() => RemindersBloc(getSettings: sl(), apply: sl(), requestPermission: sl(), sendTest: sl(), getPrograms: sl()));
}
