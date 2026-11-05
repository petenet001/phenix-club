import '../../../../core/network/api_client.dart';

class AuthRemoteDataSource {
  const AuthRemoteDataSource(this._api);
  final ApiClient _api;

  /// { token, member }
  Future<Map<String, dynamic>> login(String phone, String password) =>
      _api.post('/member/login', data: {'phone': phone, 'password': password, 'device_name': 'app-mobile'});

  Future<void> logout() => _api.post('/member/logout');

  Future<void> changePassword(String current, String password, String confirmation) => _api.put('/member/password', data: {
        'current_password': current,
        'password': password,
        'password_confirmation': confirmation,
      });
}
