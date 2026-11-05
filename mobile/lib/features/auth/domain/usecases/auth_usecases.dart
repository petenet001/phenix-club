import '../../../../core/result/result.dart';
import '../entities/member_profile.dart';
import '../repositories/auth_repository.dart';

class LoginUseCase {
  const LoginUseCase(this._repository);
  final AuthRepository _repository;

  Future<Result<MemberProfile>> call({required String phone, required String password}) =>
      _repository.login(phone: phone.trim(), password: password);
}

class CheckSessionUseCase {
  const CheckSessionUseCase(this._repository);
  final AuthRepository _repository;

  Future<bool> call() => _repository.hasSession();
}

class LogoutUseCase {
  const LogoutUseCase(this._repository);
  final AuthRepository _repository;

  Future<void> call() => _repository.logout();
}

class ClearSessionUseCase {
  const ClearSessionUseCase(this._repository);
  final AuthRepository _repository;

  Future<void> call() => _repository.clearSession();
}

class ChangePasswordUseCase {
  const ChangePasswordUseCase(this._repository);
  final AuthRepository _repository;

  Future<Result<void>> call({required String current, required String password, required String confirmation}) =>
      _repository.changePassword(current: current, password: password, confirmation: confirmation);
}
