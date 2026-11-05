import 'dart:ui';

import '../theme/app_theme.dart';

/// Couleur d'un statut d'adhérent (codes de l'API) :
/// actif = vert, en attente de validation = ambre, inactif (pas d'abonnement valide) et suspendu = rouge.
Color memberStatusColor(String status, AppPalette p) => switch (status) {
  'active' => p.success,
  'pending' => p.warning,
  'inactive' || 'suspended' => p.danger,
  _ => p.textMuted,
};

/// Couleur d'un statut d'abonnement : en cours = vert, à venir = ambre, expiré = rouge, annulé = gris.
Color subscriptionStatusColor(String status, AppPalette p) => switch (status) {
  'active' => p.success,
  'pending' => p.warning,
  'expired' => p.danger,
  _ => p.textMuted,
};
