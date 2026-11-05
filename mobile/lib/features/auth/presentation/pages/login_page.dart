import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/ui.dart';
import '../bloc/auth_bloc.dart';
import '../bloc/login_bloc.dart';

class LoginPage extends StatelessWidget {
  const LoginPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(create: (_) => sl<LoginBloc>(), child: const _LoginView());
  }
}

class _LoginView extends StatefulWidget {
  const _LoginView();

  @override
  State<_LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<_LoginView> {
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    context.read<LoginBloc>().add(LoginSubmitted(phone: _phone.text, password: _password.text));
  }

  @override
  Widget build(BuildContext context) {
    final expiredMessage = context.select((AuthBloc b) => b.state.message);

    return BlocListener<LoginBloc, LoginState>(
      listener: (context, state) {
        if (state.status == LoginStatus.success) context.read<AuthBloc>().add(const AuthLoggedIn());
      },
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  FadeSlideIn(
                    child: Center(
                      child: Hero(
                        tag: 'logo',
                        child: ClipRRect(borderRadius: BorderRadius.circular(24), child: Image.asset('assets/images/logo-phenix.png', width: 120, height: 120)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  FadeSlideIn(
                    delay: Duration(milliseconds: 80),
                    child: Column(children: [
                      Text('Bienvenue', textAlign: TextAlign.center, style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                      SizedBox(height: 6),
                      Text('Connectez-vous avec le code remis à l\'accueil.', textAlign: TextAlign.center, style: TextStyle(color: context.colors.textMuted)),
                    ]),
                  ),
                  const SizedBox(height: 32),
                  if (expiredMessage != null) ...[
                    _Banner(message: expiredMessage, color: context.colors.warning),
                    const SizedBox(height: 16),
                  ],
                  FadeSlideIn(
                    delay: const Duration(milliseconds: 160),
                    child: Column(children: [
                      TextField(
                        key: const Key('login_phone'),
                        controller: _phone,
                        keyboardType: TextInputType.phone,
                        textInputAction: TextInputAction.next,
                        autofillHints: const [AutofillHints.telephoneNumber],
                        decoration: const InputDecoration(labelText: 'Téléphone', hintText: '06 123 45 67', prefixIcon: Icon(Icons.phone_rounded)),
                      ),
                      const SizedBox(height: 14),
                      TextField(
                        key: const Key('login_password'),
                        controller: _password,
                        obscureText: _obscure,
                        keyboardType: TextInputType.number,
                        onSubmitted: (_) => _submit(),
                        autofillHints: const [AutofillHints.password],
                        decoration: InputDecoration(
                          labelText: 'Code d\'accès',
                          prefixIcon: const Icon(Icons.lock_rounded),
                          suffixIcon: IconButton(
                            tooltip: _obscure ? 'Afficher le code' : 'Masquer le code',
                            icon: Icon(_obscure ? Icons.visibility_rounded : Icons.visibility_off_rounded),
                            onPressed: () => setState(() => _obscure = !_obscure),
                          ),
                        ),
                      ),
                    ]),
                  ),
                  const SizedBox(height: 20),
                  BlocBuilder<LoginBloc, LoginState>(
                    builder: (context, state) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 250),
                        child: state.status == LoginStatus.failure && state.message != null
                            ? Padding(padding: const EdgeInsets.only(bottom: 16), child: _Banner(key: ValueKey(state.message), message: state.message!, color: context.colors.danger))
                            : const SizedBox.shrink(),
                      ),
                      FilledButton(
                        key: const Key('login_submit'),
                        onPressed: state.status == LoginStatus.submitting ? null : _submit,
                        child: state.status == LoginStatus.submitting
                            ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5))
                            : const Text('Se connecter'),
                      ),
                    ]),
                  ),
                  const SizedBox(height: 24),
                  Text('Pas encore de code ? Demandez-le à l\'accueil de Phenix Club.', textAlign: TextAlign.center, style: TextStyle(color: context.colors.textMuted, fontSize: 13)),
                ]),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({super.key, required this.message, required this.color});
  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: color.withValues(alpha: .14), borderRadius: BorderRadius.circular(14)),
      child: Text(message, style: TextStyle(color: color)),
    );
  }
}
