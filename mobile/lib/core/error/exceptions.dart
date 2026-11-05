/// Levée par les sources de données ; convertie en [Failure] dans les dépôts.
class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.code, this.fieldErrors = const {}});

  final String message;
  final int? statusCode;
  final String? code;
  final Map<String, String> fieldErrors;

  bool get isUnauthorized => statusCode == 401;
  bool get isNetwork => statusCode == null;

  @override
  String toString() => 'ApiException($statusCode, $code, $message)';
}
