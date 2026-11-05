import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../bloc/check_in_bloc.dart';

/// Scan du QR code de la borne. Renvoie `true` à l'écran précédent si la présence est enregistrée.
class ScanPage extends StatelessWidget {
  const ScanPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(create: (_) => sl<CheckInBloc>(), child: const _ScanView());
  }
}

class _ScanView extends StatefulWidget {
  const _ScanView();

  @override
  State<_ScanView> createState() => _ScanViewState();
}

class _ScanViewState extends State<_ScanView> {
  final _controller = MobileScannerController(detectionSpeed: DetectionSpeed.noDuplicates, formats: const [BarcodeFormat.qrCode]);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<CheckInBloc, CheckInState>(
      listenWhen: (a, b) => a.status != b.status,
      listener: (context, state) {
        if (state.status == CheckInStatus.success) {
          HapticFeedback.heavyImpact();
          _controller.stop();
        } else if (state.status == CheckInStatus.failure) {
          HapticFeedback.vibrate();
        }
      },
      builder: (context, state) {
        return Scaffold(
          backgroundColor: Colors.black,
          appBar: AppBar(
            backgroundColor: Colors.transparent,
            foregroundColor: Colors.white,
            title: const Text('Pointer ma présence'),
            leading: IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => context.pop(state.status == CheckInStatus.success)),
            actions: [
              IconButton(tooltip: 'Lampe', icon: const Icon(Icons.flashlight_on_rounded), onPressed: _controller.toggleTorch),
            ],
          ),
          extendBodyBehindAppBar: true,
          body: Stack(fit: StackFit.expand, children: [
            MobileScanner(
              controller: _controller,
              onDetect: (capture) {
                final value = capture.barcodes.firstOrNull?.rawValue;
                if (value != null) context.read<CheckInBloc>().add(CheckInCodeScanned(value));
              },
              errorBuilder: (context, error) => const _CameraError(),
            ),
            const _Viewfinder(),
            Positioned(
              left: 20,
              right: 20,
              bottom: 32,
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 300),
                transitionBuilder: (child, anim) => FadeTransition(
                  opacity: anim,
                  child: SlideTransition(position: Tween(begin: const Offset(0, .25), end: Offset.zero).animate(anim), child: child),
                ),
                child: switch (state.status) {
                  CheckInStatus.scanning => const _Hint(key: ValueKey('hint')),
                  CheckInStatus.submitting => const _Panel(key: ValueKey('submitting'), child: Row(children: [
                      SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5)),
                      SizedBox(width: 14),
                      Text('Enregistrement…'),
                    ])),
                  CheckInStatus.success => _Success(key: const ValueKey('success'), state: state),
                  CheckInStatus.failure => _Failure(key: const ValueKey('failure'), state: state),
                },
              ),
            ),
          ]),
        );
      },
    );
  }
}

class _Viewfinder extends StatelessWidget {
  const _Viewfinder();

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Center(
        child: TweenAnimationBuilder<double>(
          tween: Tween(begin: .9, end: 1),
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeOutBack,
          builder: (_, s, child) => Transform.scale(scale: s, child: child),
          child: Container(
            width: 250,
            height: 250,
            decoration: BoxDecoration(border: Border.all(color: context.colors.brand, width: 3), borderRadius: BorderRadius.circular(28)),
          ),
        ),
      ),
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: context.colors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: context.colors.border)),
      child: child,
    );
  }
}

class _Hint extends StatelessWidget {
  const _Hint({super.key});

  @override
  Widget build(BuildContext context) {
    return _Panel(
      child: Row(children: [
        Icon(Icons.tv_rounded, color: context.colors.brand),
        SizedBox(width: 14),
        Expanded(child: Text('Visez le QR code affiché sur l\'écran de l\'accueil.')),
      ]),
    );
  }
}

class _Success extends StatelessWidget {
  const _Success({super.key, required this.state});
  final CheckInState state;

  @override
  Widget build(BuildContext context) {
    final r = state.result!;
    return _Panel(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        TweenAnimationBuilder<double>(
          tween: Tween(begin: 0, end: 1),
          duration: const Duration(milliseconds: 600),
          curve: Curves.elasticOut,
          builder: (_, s, child) => Transform.scale(scale: s, child: child),
          child: CircleAvatar(radius: 34, backgroundColor: context.colors.success, child: Icon(Icons.check_rounded, size: 42, color: Colors.white)),
        ),
        const SizedBox(height: 14),
        Text(r.message, textAlign: TextAlign.center, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text('${Fmt.time(r.checkedInAt)}${r.plan != null ? ' · ${r.plan}' : ''}', style: TextStyle(color: context.colors.textMuted)),
        if (r.sessionsCount != null) Text('Séances utilisées : ${r.sessionsUsed}/${r.sessionsCount}', style: TextStyle(color: context.colors.textMuted)),
        if (r.warning != null) ...[
          const SizedBox(height: 10),
          Text(r.warning!, textAlign: TextAlign.center, style: TextStyle(color: context.colors.warning)),
        ],
        const SizedBox(height: 16),
        FilledButton(onPressed: () => context.pop(true), child: const Text('Terminer')),
      ]),
    );
  }
}

class _Failure extends StatelessWidget {
  const _Failure({super.key, required this.state});
  final CheckInState state;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        CircleAvatar(radius: 30, backgroundColor: context.colors.danger, child: Icon(Icons.close_rounded, size: 36, color: Colors.white)),
        const SizedBox(height: 14),
        Text(state.message ?? 'Pointage impossible.', textAlign: TextAlign.center, style: const TextStyle(fontSize: 16)),
        const SizedBox(height: 16),
        if (state.canRescan)
          FilledButton(onPressed: () => context.read<CheckInBloc>().add(const CheckInRetried()), child: const Text('Scanner à nouveau'))
        else
          OutlinedButton(onPressed: () => context.pop(false), child: const Text('Fermer')),
      ]),
    );
  }
}

class _CameraError extends StatelessWidget {
  const _CameraError();

  @override
  Widget build(BuildContext context) {
    return const ColoredBox(
      color: Colors.black,
      child: Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Text(
            'Caméra indisponible. Autorisez l\'accès à la caméra dans les réglages du téléphone, ou pointez à l\'accueil.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white70),
          ),
        ),
      ),
    );
  }
}
