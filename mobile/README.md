# Phenix Club : application adhérent (Flutter)

L'adhérent suit son abonnement, ses paiements, ce qu'il doit, ses présences et ses programmes, et **pointe en scannant le QR code de la borne** affichée à l'accueil.

## Lancer

```bash
cd mobile
flutter pub get
flutter run                                   # simulateur iOS -> localhost:8000, émulateur Android -> 10.0.2.2:8000
flutter run --dart-define=API_URL=http://192.168.1.20:8000/api   # téléphone réel sur le même Wi-Fi
```

Sur un téléphone réel, l'API doit écouter sur le réseau : `php artisan serve --host 0.0.0.0`.

Comptes de démonstration : téléphone `061000001` à `061000011` (ex. `061000004`, Prisca Samba), code `123456`.
Un vrai adhérent reçoit son code depuis sa fiche sur le bureau (carte « Application mobile », bouton « Activer l'accès »).

## Pointage

1. Sur le bureau, page **Pointage** → **Ouvrir la borne de pointage** (gérant et accueil), idéalement en plein écran sur un écran visible à l'entrée.
2. Le QR change toutes les 30 s (signé côté serveur, sans stockage) : une photo envoyée à quelqu'un hors de la salle expire en moins d'une minute.
3. Dans l'application, bouton central → scan → mêmes règles que le pointage à l'accueil (abonnement, tolérance, séances, une fois par jour). L'adhérent ne peut jamais forcer.

## Architecture (Clean Architecture + BLoC)

```
lib/
  core/            config, DI (get_it), client HTTP (dio), erreurs/Failure, Result, routeur (go_router), thème, widgets
  features/<f>/
    domain/        entités, contrats de dépôt, cas d'usage (aucune dépendance Flutter/HTTP)
    data/          modèles JSON, implémentations des dépôts
    presentation/  bloc (événements / états), pages, widgets
```

Fonctionnalités : `auth` (connexion, session, expiration), `home` (accueil), `checkin` (scan), `payments` (à régler, historique, reçu),
`attendance` (calendrier des présences), `programs` (entraînement / alimentation), `plans` (tarifs), `profile` (carte de membre, code d'accès),
`reminders` (rappels de séance : notifications locales hebdomadaires les jours du programme du coach, heure et jours réglables), `settings` (thème).

## Tests

```bash
flutter analyze
flutter test                                  # blocs, cas d'usage, modèles, client HTTP
flutter drive --driver test_driver/integration_test.dart --target integration_test/app_test.dart -d <simulateur>
                                              # parcours réel contre l'API locale, captures dans build/screenshots/
```

## Avant publication

- API en HTTPS et `API_URL` de production.
- Icône et écran de lancement (ex. `flutter_launcher_icons`), identifiant `cg.phenixclub.phenix_client`, signature iOS / Android.
