import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:phenix_client/core/error/exceptions.dart';
import 'package:phenix_client/core/network/api_client.dart';
import 'package:phenix_client/core/network/session_events.dart';
import 'package:phenix_client/core/storage/token_storage.dart';

class _Storage extends Mock implements FlutterSecureStorage {}

/// Adaptateur HTTP factice : renvoie la réponse préparée et mémorise la requête.
class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.status, this.body);
  final int status;
  final String body;
  RequestOptions? last;

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<List<int>>? requestStream, Future<void>? cancelFuture) async {
    last = options;
    return ResponseBody.fromString(body, status, headers: {Headers.contentTypeHeader: ['application/json']});
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  late _Storage storage;
  late SessionEvents session;

  setUp(() {
    storage = _Storage();
    session = SessionEvents();
    when(() => storage.read(key: any(named: 'key'))).thenAnswer((_) async => 'tok');
  });

  ApiClient client(_FakeAdapter adapter) {
    final dio = Dio(BaseOptions(baseUrl: 'http://api.test'))..httpClientAdapter = adapter;
    return ApiClient(baseUrl: 'http://api.test', tokens: TokenStorage(storage), session: session, dio: dio);
  }

  test('envoie le token Bearer', () async {
    final adapter = _FakeAdapter(200, '{"data":{}}');
    await client(adapter).get('/member/me');
    expect(adapter.last!.headers['Authorization'], 'Bearer tok');
  });

  test('traduit une erreur métier avec son code stable', () async {
    final adapter = _FakeAdapter(422, '{"error":"already_checked_in_today","message":"Déjà pointé"}');
    expect(
      () => client(adapter).post('/member/check-in', data: {'code': 'x'}),
      throwsA(isA<ApiException>().having((e) => e.code, 'code', 'already_checked_in_today').having((e) => e.message, 'message', 'Déjà pointé')),
    );
  });

  test('prend le premier message de validation', () async {
    final adapter = _FakeAdapter(422, '{"message":"x","errors":{"phone":["Téléphone ou code incorrect."]}}');
    expect(() => client(adapter).post('/member/login'), throwsA(isA<ApiException>().having((e) => e.fieldErrors['phone'], 'phone', 'Téléphone ou code incorrect.')));
  });

  test('signale une session expirée sur 401, sauf à la connexion', () async {
    final events = <void>[];
    session.onExpired.listen(events.add);

    await expectLater(client(_FakeAdapter(401, '{}')).get('/member/me'), throwsA(isA<ApiException>()));
    await expectLater(client(_FakeAdapter(401, '{}')).post('/member/login'), throwsA(isA<ApiException>()));
    await Future<void>.delayed(Duration.zero);

    expect(events, hasLength(1));
  });
}
