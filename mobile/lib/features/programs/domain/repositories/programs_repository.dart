import '../../../../core/result/result.dart';
import '../entities/program.dart';

abstract interface class ProgramsRepository {
  Future<Result<List<Program>>> getPrograms();
}
