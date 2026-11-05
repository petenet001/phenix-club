import '../../../../core/result/result.dart';
import '../entities/program.dart';
import '../repositories/programs_repository.dart';

class GetProgramsUseCase {
  const GetProgramsUseCase(this._repository);
  final ProgramsRepository _repository;
  Future<Result<List<Program>>> call() => _repository.getPrograms();
}
