import '../../../../core/error/guard.dart';
import '../../../../core/result/result.dart';
import '../../../../core/storage/token_storage.dart';
import '../../domain/entities/member_profile.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_remote_data_source.dart';
import '../models/member_profile_model.dart';

class AuthRepositoryImpl implements AuthRepository {
  const AuthRepositoryImpl(this._remote, this._tokens);

  final AuthRemoteDataSource _remote;
  final TokenStorage _tokens;

  @override
  Future<Result<MemberProfile>> login({required String phone, required String password}) => guard(() async {
        final json = await _remote.login(phone, password);
        await _tokens.write(json['token'] as String);
        return MemberProfileModel.fromJson(json['member'] as Map<String, dynamic>);
      });

  @override
  Future<bool> hasSession() async => (await _tokens.read()) != null;

  @override
  Future<void> logout() async {
    // La déconnexion locale doit réussir même hors ligne
    await guard(_remote.logout);
    await _tokens.clear();
  }

  @override
  Future<void> clearSession() => _tokens.clear();

  @override
  Future<Result<void>> changePassword({required String current, required String password, required String confirmation}) =>
      guard(() => _remote.changePassword(current, password, confirmation));
}
