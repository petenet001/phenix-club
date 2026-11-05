# Backend Laravel 13 - Phenix Club Manager


```
app/Enums/                     rôles, statuts, modes de paiement, canaux
app/Models/                    User, Member, SubscriptionPlan, Subscription, Payment, Attendance, Reminder, Setting
app/Services/                  MemberService (statut, retard), SubscriptionService (souscription, renouvellement, tâche quotidienne)
app/Http/Controllers/Api/      Auth, Member, SubscriptionPlan, Subscription
app/Http/Requests/             validation (Form Requests)
app/Http/Resources/            User, Member, SubscriptionPlan, Subscription
app/Http/Middleware/EnsureRole role:manager
app/Exceptions/BusinessRuleException  422 avec code d'erreur stable
app/Console/Commands/          subscriptions:refresh-status
bootstrap/app.php              alias role, réponses JSON 401 / 404
routes/api.php, routes/console.php
database/migrations, seeders, factories, schema.sql
tests/Feature/                 MemberApiTest, SubscriptionApiTest (Pest)
```

## Installation

Voir le README à la racine du projet.

Comptes seedés : `gerant@phenixclub.cg`, `coach@phenixclub.cg`, `accueil@phenixclub.cg`, mot de passe `password`.
Les tarifs de `SubscriptionPlanSeeder` sont indicatifs.

## Endpoints livrés

Voir `docs/08-api-endpoints.md` pour le détail. Résumé par module :

| Module | Routes |
|---|---|
| Auth | POST /login, POST /logout, GET /me |
| Adhérents | GET/POST /members, GET/PUT /members/{id}, DELETE [M], PATCH /members/{id}/status [M], POST /members/{id}/validate, GET /members/by-qr/{token}, GET /members/{id}/{subscriptions,attendances,payments,outstanding,reminders,programs} |
| Inscription en ligne | POST /public/registrations (sans auth, paramètre online_registration_enabled) |
| Formules | GET /subscription-plans, POST/PUT/DELETE [M] |
| Abonnements | POST /subscriptions, GET /subscriptions/{id}, PATCH /subscriptions/{id}/cancel [M], GET /members/{id}/subscriptions/suggested-start |
| Pointage | GET /attendances/check, POST /attendances, GET /attendances, DELETE /attendances/{id} [M] |
| Paiements | GET/POST /payments, POST /payments/batch, GET /payments/{id}, GET /payments/{id}/receipt?format=json\|pdf, PATCH /payments/{id}/cancel [M] |
| Recouvrement | GET /late-payments, GET /absences, GET /reminders/preview, POST /reminders |
| Caisse | GET /expense-categories (+ POST/PUT/DELETE [M]), GET/POST /expenses, PUT /expenses/{id}, PATCH /expenses/{id}/cancel [M], GET /cashbook, GET /cashbook/export [M] |
| Programmes | GET /programs [coach,M], POST /members/{id}/programs [coach,M], GET /programs/{id}, PUT [coach,M], PATCH /programs/{id}/{approve,decline,archive} [coach,M] |
| Pilotage | GET /dashboard [M], GET /settings, PUT /settings [M], GET/POST/PUT/DELETE /users [M] |

Erreur métier : `{ "error": "code_stable", "message": "...", "details": {} }` en 422 (403 pour les actions réservées).
