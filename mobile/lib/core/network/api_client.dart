import 'package:dio/dio.dart';

import '../error/exceptions.dart';
import '../storage/token_storage.dart';
import 'session_events.dart';

/// Client HTTP de l'API Phenix : ajoute le token, uniformise les erreurs en [ApiException].
class ApiClient {
  ApiClient({required String baseUrl, required TokenStorage tokens, required SessionEvents session, Dio? dio})
      : _dio = dio ??
            Dio(BaseOptions(
              baseUrl: baseUrl,
              connectTimeout: const Duration(seconds: 10),
              receiveTimeout: const Duration(seconds: 15),
              headers: {'Accept': 'application/json'},
            )) {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await tokens.read();
        if (token != null) options.headers['Authorization'] = 'Bearer $token';
        handler.next(options);
      },
      onError: (error, handler) {
        final isLogin = error.requestOptions.path.endsWith('/login');
        if (error.response?.statusCode == 401 && !isLogin) session.notifyExpired();
        handler.next(error);
      },
    ));
  }

  final Dio _dio;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) =>
      _send(() => _dio.get<dynamic>(path, queryParameters: query));

  Future<Map<String, dynamic>> post(String path, {Object? data}) => _send(() => _dio.post<dynamic>(path, data: data));

  Future<Map<String, dynamic>> put(String path, {Object? data}) => _send(() => _dio.put<dynamic>(path, data: data));

  Future<Map<String, dynamic>> _send(Future<Response<dynamic>> Function() request) async {
    try {
      final response = await request();
      final data = response.data;
      if (data is Map<String, dynamic>) return data;
      return const {};
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  static ApiException _toApiException(DioException e) {
    final response = e.response;
    if (response == null) {
      return const ApiException('Impossible de joindre le serveur. Vérifiez votre connexion.');
    }

    final body = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
    final errors = <String, String>{};
    final rawErrors = body['errors'];
    if (rawErrors is Map) {
      rawErrors.forEach((key, value) {
        if (value is List && value.isNotEmpty) errors['$key'] = '${value.first}';
      });
    }

    return ApiException(
      errors.values.firstOrNull ?? body['message'] as String? ?? 'Erreur inattendue (${response.statusCode}).',
      statusCode: response.statusCode,
      code: body['error'] as String?,
      fieldErrors: errors,
    );
  }
}
