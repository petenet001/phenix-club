import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:phenix_client/core/error/failures.dart';
import 'package:phenix_client/core/result/result.dart';
import 'package:phenix_client/features/auth/domain/entities/member_profile.dart';
import 'package:phenix_client/features/auth/domain/repositories/auth_repository.dart';
import 'package:phenix_client/features/auth/domain/usecases/auth_usecases.dart';
import 'package:phenix_client/features/auth/presentation/bloc/login_bloc.dart';

class _Repo extends Mock implements AuthRepository {}

void main() {
  late _Repo repo;
  final profile = MemberProfile(
    id: 1, code: 'PHX-000001', firstName: 'Alain', lastName: 'Mbemba', fullName: 'Alain Mbemba', phone: '+242061000001',
    status: 'active', statusLabel: 'Actif', joinedAt: DateTime(2026), qrToken: 'qr',
  );

  setUp(() => repo = _Repo());

  blocTest<LoginBloc, LoginState>(
    'connecte avec téléphone et code',
    setUp: () => when(() => repo.login(phone: '061000001', password: '123456')).thenAnswer((_) async => Success(profile)),
    build: () => LoginBloc(LoginUseCase(repo)),
    act: (bloc) => bloc.add(const LoginSubmitted(phone: ' 061000001 ', password: '123456')),
    expect: () => [const LoginState(status: LoginStatus.submitting), const LoginState(status: LoginStatus.success)],
  );

  blocTest<LoginBloc, LoginState>(
    'affiche le message de l\'API en cas d\'échec',
    setUp: () => when(() => repo.login(phone: any(named: 'phone'), password: any(named: 'password')))
        .thenAnswer((_) async => const Err(ServerFailure('Téléphone ou code d\'accès incorrect.'))),
    build: () => LoginBloc(LoginUseCase(repo)),
    act: (bloc) => bloc.add(const LoginSubmitted(phone: '061000001', password: 'x')),
    expect: () => [const LoginState(status: LoginStatus.submitting), const LoginState(status: LoginStatus.failure, message: 'Téléphone ou code d\'accès incorrect.')],
  );

  blocTest<LoginBloc, LoginState>(
    'refuse un formulaire vide sans appeler l\'API',
    build: () => LoginBloc(LoginUseCase(repo)),
    act: (bloc) => bloc.add(const LoginSubmitted(phone: '', password: '')),
    expect: () => [isA<LoginState>().having((s) => s.status, 'status', LoginStatus.failure)],
    verify: (_) => verifyNever(() => repo.login(phone: any(named: 'phone'), password: any(named: 'password'))),
  );
}
