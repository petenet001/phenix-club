import '../../../../core/config/app_config.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/result/result.dart';
import '../entities/check_in_result.dart';
import '../repositories/check_in_repository.dart';

/// Pointage par scan du QR code de la borne. Un QR étranger est refusé sans appel réseau.
class CheckInUseCase {
  const CheckInUseCase(this._repository);
  final CheckInRepository _repository;

  static const notClubQr = 'not_club_qr';

  Future<Result<CheckInResult>> call(String scanned) async {
    final code = scanned.trim();
    if (!code.startsWith(AppConfig.checkInQrPrefix)) {
      return const Err(ServerFailure('Ce QR code n\'est pas celui de la borne Phenix Club.', code: notClubQr));
    }
    return _repository.checkIn(code);
  }
}
