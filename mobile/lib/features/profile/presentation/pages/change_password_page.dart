import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/di/injection.dart';
import '../../../../core/theme/app_theme.dart';
import '../bloc/change_password_bloc.dart';

class ChangePasswordPage extends StatelessWidget {
  const ChangePasswordPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => sl<ChangePasswordBloc>(),
      child: const _ChangePasswordView(),
    );
  }
}

class _ChangePasswordView extends StatefulWidget {
  const _ChangePasswordView();

  @override
  State<_ChangePasswordView> createState() => _ChangePasswordViewState();
}

class _ChangePasswordViewState extends State<_ChangePasswordView> {
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<ChangePasswordBloc, ChangePasswordState>(
      listener: (context, state) {
        if (state.status == ChangePasswordStatus.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Code d\'accès modifié.')),
          );
          context.pop();
        }
      },
      builder: (context, state) {
        InputDecoration deco(String label, String field) => InputDecoration(
          labelText: label,
          errorText: state.fieldErrors[field],
          suffixIcon: IconButton(
            icon: Icon(
              _obscure
                  ? Icons.visibility_rounded
                  : Icons.visibility_off_rounded,
            ),
            onPressed: () => setState(() => _obscure = !_obscure),
          ),
        );
        return Scaffold(
          appBar: AppBar(title: const Text('Changer mon code')),
          body: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Choisissez un code d\'au moins 6 caractères. Vos autres appareils seront déconnectés.',
                style: TextStyle(color: context.colors.textMuted),
              ),
              const SizedBox(height: 20),
              TextField(
                controller: _current,
                obscureText: _obscure,
                decoration: deco('Code actuel', 'current_password'),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: _password,
                obscureText: _obscure,
                decoration: deco('Nouveau code', 'password'),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: _confirmation,
                obscureText: _obscure,
                decoration: deco(
                  'Confirmer le nouveau code',
                  'password_confirmation',
                ),
              ),
              if (state.message != null && state.fieldErrors.isEmpty) ...[
                const SizedBox(height: 14),
                Text(
                  state.message!,
                  style: TextStyle(color: context.colors.danger),
                ),
              ],
              const SizedBox(height: 24),
              FilledButton(
                onPressed: state.status == ChangePasswordStatus.submitting
                    ? null
                    : () => context.read<ChangePasswordBloc>().add(
                        ChangePasswordSubmitted(
                          current: _current.text,
                          password: _password.text,
                          confirmation: _confirmation.text,
                        ),
                      ),
                child: state.status == ChangePasswordStatus.submitting
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(strokeWidth: 2.5),
                      )
                    : const Text('Enregistrer'),
              ),
            ],
          ),
        );
      },
    );
  }
}
