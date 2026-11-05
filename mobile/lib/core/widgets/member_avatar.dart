import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Avatar de l'adhérent : sa photo si l'accueil en a pris une, sinon ses initiales.
/// Si la photo ne se charge pas, les initiales restent affichées.
class MemberAvatar extends StatelessWidget {
  const MemberAvatar({
    super.key,
    required this.initials,
    this.photoUrl,
    this.radius = 26,
    this.onTap,
  });

  final String initials;
  final String? photoUrl;
  final double radius;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.colors;
    final avatar = Container(
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: c.border, width: 1.5),
      ),
      child: CircleAvatar(
        radius: radius,
        backgroundColor: c.surfaceHigh,
        foregroundImage: photoUrl != null ? NetworkImage(photoUrl!) : null,
        onForegroundImageError: photoUrl != null ? (_, _) {} : null,
        child: initials.isEmpty
            ? Icon(Icons.person_rounded, size: radius, color: c.textMuted)
            : Text(
                initials,
                style: TextStyle(
                  fontSize: radius * .62,
                  fontWeight: FontWeight.w600,
                  color: c.text,
                  letterSpacing: .5,
                ),
              ),
      ),
    );

    if (onTap == null) return avatar;
    return Semantics(
      button: true,
      label: 'Mon profil',
      child: GestureDetector(onTap: onTap, child: avatar),
    );
  }
}
