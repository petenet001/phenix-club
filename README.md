# Phenix Club Manager

Application de gestion de la salle de sport Phenix Club (Brazzaville) : adhérents, abonnements, pointage, paiements et reçus, retards, absences, relances, caisse, programmes d'entraînement et d'alimentation, tableau de bord.

```
docs/      dossier d'analyse (cahier des charges, règles de gestion, MCD, MLD, flux, cas d'utilisation, API)
backend/   API Laravel 13 (PostgreSQL, Sanctum)
frontend/  application React 18 (Vite, React Router, TanStack Query, Tailwind)
mobile/    application adhérent Flutter (BLoC, Clean Architecture) - voir mobile/README.md
```

## 1. Backend

Prérequis : PHP 8.3+, Composer, PostgreSQL.

```bash
cd backend
composer install
cp .env.example .env      # adapter DB_USERNAME et DB_PASSWORD
php artisan key:generate
createdb phenix_club
createdb phenix_club_test
php artisan storage:link
php artisan migrate --seed
php artisan serve         # http://localhost:8000
php artisan test          # 61 tests Pest
```

Repartir d'une base de démonstration propre : `php artisan migrate:fresh --seed`.
Reçus en PDF (optionnel, sinon version HTML imprimable) : `composer require barryvdh/laravel-dompdf`.

Comptes : `gerant@phenixclub.cg`, `coach@phenixclub.cg`, `accueil@phenixclub.cg`, mot de passe `password`.
Planificateur (statuts quotidiens) : `* * * * * cd /chemin && php artisan schedule:run >> /dev/null 2>&1`.

## 2. Frontend

```bash
cd phenix/frontend
npm install
cp .env.example .env      # VITE_API_URL=http://localhost:8000/api
npm run dev               # http://localhost:5173
npm run build             # dist/ à déployer en statique
npm test
```

En développement, Vite proxyfie `/api` et `/storage` vers `localhost:8000`.

## 3. Écrans

| Écran | Rôle | Contenu |
|---|---|---|
| Tableau de bord | gérant | effectif, présences, encaissements, retards, demandes à valider, programmes à traiter, courbe 30 jours |
| Pointage | tous | borne plein écran pour l'application adhérent (QR tournant 30 s, agent et gérant), scan de carte membre ou recherche, écran vert / orange / rouge, forçage gérant, présents du jour |
| Adhérents | tous | liste filtrable, fiche complète, inscription, souscription, encaissement groupé (frais + abonnement), reçus, relances, programmes |
| Paiements | agent, gérant | journal par période, totaux par mode et type, annulation gérant, reçu |
| Retards | agent, gérant | liste calculée, montant dû, encaissement et relance directs |
| Absences | tous | actifs sans pointage, relance |
| Caisse | agent, gérant | entrées, sorties par catégorie, solde, cumul annuel, manquants, export Excel |
| Programmes | coach, gérant | propositions à valider ou refuser, création, imposition, archivage |
| Paramètres | gérant | règles (tolérance, seuils, frais d'inscription, inscription en ligne), formules, comptes, catégories |

## 4. Déploiement

- o2switch : API sous Passenger (`public/` comme racine), frontend `dist/` sur un sous-domaine, cron o2switch pour `schedule:run`
- VPS Hetzner : Nginx + PHP-FPM 8.3 + PostgreSQL 16, `dist/` servi par Nginx, Certbot

Règle de réécriture pour le frontend (SPA) : toutes les routes vers `index.html`.

## 5. Phase 2

- Application adhérent : proposition de programme et de jours par l'adhérent (l'API `training_programs` et le champ `proposed_by_member` sont prêts)
- Passerelle SMS / WhatsApp réelle : implémenter `App\Services\Messaging\MessageGateway` et la lier dans `AppServiceProvider` (aujourd'hui `LogGateway` journalise et ouvre un lien wa.me prérempli)
- Paiement mobile money intégré
