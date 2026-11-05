import 'package:intl/intl.dart';

/// Formats français utilisés dans toute l'application.
class Fmt {
  const Fmt._();

  static final _money = NumberFormat.decimalPattern('fr_FR');

  static String fcfa(num amount) => '${_money.format(amount).replaceAll(' ', ' ')} FCFA';

  static String date(DateTime? d) => d == null ? '-' : DateFormat('dd/MM/yyyy', 'fr_FR').format(d.toLocal());

  static String dateLong(DateTime d) => DateFormat('EEEE d MMMM', 'fr_FR').format(d.toLocal());

  static String time(DateTime d) => DateFormat("HH'h'mm", 'fr_FR').format(d.toLocal());

  static String monthYear(DateTime d) {
    final s = DateFormat('MMMM yyyy', 'fr_FR').format(d.toLocal());
    return s[0].toUpperCase() + s.substring(1);
  }

  static DateTime? parse(Object? value) => value is String && value.isNotEmpty ? DateTime.tryParse(value) : null;
}
