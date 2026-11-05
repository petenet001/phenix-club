import '../../../../core/result/result.dart';
import '../entities/member_profile.dart';

abstract interface class AuthRepository {
  Future<Result<MemberProfile>> login({required String phone, required String password});

  Future<bool> hasSession();

  Future<void> logout();

  /// Supprime le token local sans appeler l'API (session déjà expirée).
  Future<void> clearSession();

  Future<Result<void>> changePassword({required String current, required String password, required String confirmation});
}
