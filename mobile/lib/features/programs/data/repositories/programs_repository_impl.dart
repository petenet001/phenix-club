import '../../../../core/error/guard.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/result/result.dart';
import '../../../../core/utils/formatters.dart';
import '../../domain/entities/program.dart';
import '../../domain/repositories/programs_repository.dart';

class ProgramsRepositoryImpl implements ProgramsRepository {
  const ProgramsRepositoryImpl(this._api);
  final ApiClient _api;

  static Program fromJson(Map<String, dynamic> j) => Program(
        id: (j['id'] as num).toInt(),
        kind: j['kind'] as String? ?? 'training',
        kindLabel: j['kind_label'] as String? ?? '',
        title: j['title'] as String? ?? '',
        status: j['status'] as String? ?? '',
        statusLabel: j['status_label'] as String? ?? '',
        isEffective: j['is_effective'] == true,
        author: j['author'] as String?,
        startsOn: Fmt.parse(j['starts_on']),
        endsOn: Fmt.parse(j['ends_on']),
        content: j['content'] is Map ? Map<String, dynamic>.from(j['content'] as Map) : const {},
        reviewNote: j['review_note'] as String?,
      );

  @override
  Future<Result<List<Program>>> getPrograms() => guard(() async {
        final json = await _api.get('/member/programs');
        return ((json['data'] as List?) ?? const []).cast<Map<String, dynamic>>().map(fromJson).toList();
      });
}
