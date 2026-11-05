# Phenix Club Manager - Dossier d'analyse et base de données

Application de gestion pour la salle de sport Phenix Club (Brazzaville).
Stack : Laravel 11 (API REST, PostgreSQL, Sanctum) + React 18 (Vite, React Router, TanStack Query, Tailwind).

## Contenu du dossier

| Fichier | Contenu |
|---|---|
| `docs/01-cahier-des-charges.md` | Contexte, acteurs, périmètre, fonctionnalités, contraintes |
| `docs/02-regles-de-gestion.md` | Règles métier (pointage, échéances, retards, absences) et paramètres |
| `docs/03-mcd.md` | Modèle conceptuel de données (entités, associations, diagramme) |
| `docs/04-mld-dictionnaire.md` | Modèle logique (tables PostgreSQL) et dictionnaire de données |
| `docs/05-architecture.md` | Architecture technique, structure des projets, hébergement |
| `docs/06-flux-metier.md` | Diagrammes des flux : inscription, pointage, paiement, retard, relance |
| `docs/07-user-stories.md` | User stories par acteur, priorisées |
| `docs/08-api-endpoints.md` | Plan des endpoints de l'API (méthode, route, payload, réponse) |
| `docs/09-cas-utilisation.md` | Diagramme des cas d'utilisation et fiches détaillées (scénarios nominaux et alternatifs) |
| `backend/` | Migrations, modèles Eloquent et seeders Laravel 11 |

Les diagrammes sont en Mermaid : ils s'affichent dans VS Code (extension Markdown Preview Mermaid), GitHub, GitLab ou sur mermaid.live.

## Installer la base

```bash
laravel new phenix-api --no-interaction
cd phenix-api
# copier le contenu de backend/ par-dessus le projet (migrations, models, seeders)
cp -R ../phenix/backend/app ../phenix/backend/database .
```

Dans `.env` :

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=phenix_club
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Puis :

```bash
php artisan migrate --seed
```

Comptes créés par le seeder : `gerant@phenixclub.cg` / `password` (gérant) et `accueil@phenixclub.cg` / `password` (agent).

## Ordre de développement

1. Modèle de données et migrations (ce dossier)
2. API adhérents et abonnements
3. Pointage et présences
4. Paiements et retards
5. Tableau de bord
6. Interface React écran par écran
