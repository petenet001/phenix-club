import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:phenix_client/core/error/failures.dart';
import 'package:phenix_client/core/result/result.dart';
import 'package:phenix_client/features/checkin/domain/entities/check_in_result.dart';
import 'package:phenix_client/features/checkin/domain/repositories/check_in_repository.dart';
import 'package:phenix_client/features/checkin/domain/usecases/check_in.dart';
import 'package:phenix_client/features/checkin/presentation/bloc/check_in_bloc.dart';

class _Repo extends Mock implements CheckInRepository {}

void main() {
  late _Repo repo;
  const valid = 'PHENIX-CHECKIN:12345.0123456789abcdef0123456789abcdef';
  final ok = CheckInResult(checkedInAt: DateTime(2026, 11, 4, 18), plan: 'Mensuel', message: 'Présence enregistrée, bonne séance !');

  setUp(() => repo = _Repo());

  group('CheckInUseCase', () {
    test('refuse un QR qui n\'est pas celui de la borne sans appeler l\'API', () async {
      final result = await CheckInUseCase(repo)('https://example.com');
      expect(result, isA<Err<CheckInResult>>());
      verifyNever(() => repo.checkIn(any()));
    });

    test('transmet le QR de la borne à l\'API', () async {
      when(() => repo.checkIn(valid)).thenAnswer((_) async => Success(ok));
      final result = await CheckInUseCase(repo)('  $valid ');
      expect(result, isA<Success<CheckInResult>>());
    });
  });

  group('CheckInBloc', () {
    blocTest<CheckInBloc, CheckInState>(
      'enregistre la présence',
      setUp: () => when(() => repo.checkIn(valid)).thenAnswer((_) async => Success(ok)),
      build: () => CheckInBloc(CheckInUseCase(repo)),
      act: (bloc) => bloc.add(const CheckInCodeScanned(valid)),
      expect: () => [const CheckInState(status: CheckInStatus.submitting), CheckInState(status: CheckInStatus.success, result: ok)],
    );

    blocTest<CheckInBloc, CheckInState>(
      'n\'envoie qu\'une fois quand la caméra détecte le code en rafale',
      setUp: () => when(() => repo.checkIn(valid)).thenAnswer((_) async => Success(ok)),
      build: () => CheckInBloc(CheckInUseCase(repo)),
      act: (bloc) => bloc
        ..add(const CheckInCodeScanned(valid))
        ..add(const CheckInCodeScanned(valid))
        ..add(const CheckInCodeScanned(valid)),
      verify: (_) => verify(() => repo.checkIn(valid)).called(1),
    );

    blocTest<CheckInBloc, CheckInState>(
      'déjà pointé : refus sans proposer de rescanner',
      setUp: () => when(() => repo.checkIn(valid)).thenAnswer((_) async => const Err(ServerFailure('Déjà pointé aujourd\'hui à 18h00.', code: 'already_checked_in_today'))),
      build: () => CheckInBloc(CheckInUseCase(repo)),
      act: (bloc) => bloc.add(const CheckInCodeScanned(valid)),
      skip: 1,
      expect: () => [const CheckInState(status: CheckInStatus.failure, message: 'Déjà pointé aujourd\'hui à 18h00.', code: 'already_checked_in_today')],
      verify: (bloc) => expect(bloc.state.canRescan, isFalse),
    );

    blocTest<CheckInBloc, CheckInState>(
      'QR expiré : on peut rescanner',
      setUp: () => when(() => repo.checkIn(valid)).thenAnswer((_) async => const Err(ServerFailure('QR code expiré', code: 'invalid_checkin_code'))),
      build: () => CheckInBloc(CheckInUseCase(repo)),
      act: (bloc) async {
        bloc.add(const CheckInCodeScanned(valid));
        await Future<void>.delayed(Duration.zero);
        expect(bloc.state.canRescan, isTrue);
        bloc.add(const CheckInRetried());
      },
      skip: 2,
      expect: () => [const CheckInState()],
    );
  });
}
