import 'package:flutter/cupertino.dart' show CupertinoPageTransitionsBuilder;
import 'package:flutter/material.dart';

/// Palette de l'application, déclinée en clair et en sombre.
///
/// Couleurs plates et désaturées : l'orange de la marque sert d'accent (boutons, sélection),
/// pas de dégradé ni d'ombre colorée.
@immutable
class AppPalette extends ThemeExtension<AppPalette> {
  const AppPalette({
    required this.brand,
    required this.onBrand,
    required this.background,
    required this.surface,
    required this.surfaceHigh,
    required this.border,
    required this.text,
    required this.textMuted,
    required this.success,
    required this.warning,
    required this.danger,
  });

  final Color brand;
  final Color onBrand;
  final Color background;
  final Color surface;
  final Color surfaceHigh;
  final Color border;
  final Color text;
  final Color textMuted;
  final Color success;
  final Color warning;
  final Color danger;

  static const light = AppPalette(
    brand: Color(0xFFC8611A),
    onBrand: Colors.white,
    background: Color(0xFFF6F5F2),
    surface: Colors.white,
    surfaceHigh: Color(0xFFEFEDE8),
    border: Color(0xFFE4E1DA),
    text: Color(0xFF1F2328),
    textMuted: Color(0xFF6B6F76),
    success: Color(0xFF2F7D57),
    warning: Color(0xFFA86F12),
    danger: Color(0xFFB83A2E),
  );

  static const dark = AppPalette(
    brand: Color(0xFFD5772F),
    onBrand: Colors.white,
    background: Color(0xFF141518),
    surface: Color(0xFF1C1D21),
    surfaceHigh: Color(0xFF26282D),
    border: Color(0xFF2E3035),
    text: Color(0xFFECECEC),
    textMuted: Color(0xFF9A9EA6),
    success: Color(0xFF5DAE84),
    warning: Color(0xFFD1A04A),
    danger: Color(0xFFE07A6E),
  );

  /// Fond teinté discret pour une pastille ou une icône (aplat, pas de dégradé).
  Color tint(Color c) => c.withValues(alpha: .12);

  @override
  AppPalette copyWith() => this;

  @override
  AppPalette lerp(ThemeExtension<AppPalette>? other, double t) {
    if (other is! AppPalette) return this;
    Color l(Color a, Color b) => Color.lerp(a, b, t)!;
    return AppPalette(
      brand: l(brand, other.brand),
      onBrand: l(onBrand, other.onBrand),
      background: l(background, other.background),
      surface: l(surface, other.surface),
      surfaceHigh: l(surfaceHigh, other.surfaceHigh),
      border: l(border, other.border),
      text: l(text, other.text),
      textMuted: l(textMuted, other.textMuted),
      success: l(success, other.success),
      warning: l(warning, other.warning),
      danger: l(danger, other.danger),
    );
  }
}

extension AppPaletteContext on BuildContext {
  AppPalette get colors => Theme.of(this).extension<AppPalette>()!;
}

class AppTheme {
  const AppTheme._();

  static ThemeData get light => _build(AppPalette.light, Brightness.light);

  static ThemeData get dark => _build(AppPalette.dark, Brightness.dark);

  static ThemeData _build(AppPalette p, Brightness brightness) {
    final scheme = ColorScheme.fromSeed(seedColor: p.brand, brightness: brightness).copyWith(
      primary: p.brand,
      onPrimary: p.onBrand,
      surface: p.surface,
      onSurface: p.text,
      onSurfaceVariant: p.textMuted,
      outline: p.border,
      outlineVariant: p.border,
      error: p.danger,
    );
    final cardShape = RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: p.border));

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      extensions: [p],
      scaffoldBackgroundColor: p.background,
      dividerColor: p.border,
      dividerTheme: DividerThemeData(color: p.border, space: 1),
      appBarTheme: AppBarTheme(backgroundColor: p.background, foregroundColor: p.text, elevation: 0, centerTitle: false, scrolledUnderElevation: 0),
      // Cartes plates : bordure fine au lieu d'une ombre
      cardTheme: CardThemeData(color: p.surface, elevation: 0, margin: EdgeInsets.zero, shape: cardShape),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: p.surface,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: p.border)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: p.border)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: p.brand, width: 1.5)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          elevation: 0,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          side: BorderSide(color: p.border),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(backgroundColor: p.brand, foregroundColor: p.onBrand, elevation: 0, highlightElevation: 0),
      bottomAppBarTheme: BottomAppBarThemeData(color: p.surface, elevation: 0),
      snackBarTheme: SnackBarThemeData(behavior: SnackBarBehavior.floating, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          selectedBackgroundColor: p.tint(p.brand),
          selectedForegroundColor: p.brand,
          foregroundColor: p.textMuted,
          side: BorderSide(color: p.border),
        ),
      ),
      pageTransitionsTheme: const PageTransitionsTheme(builders: {
        TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
        TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
      }),
    );
  }
}
