# 01 - Cahier des charges simplifié

## 1. Contexte

Phenix Club est une salle de sport à Brazzaville. Le suivi des inscrits, des présences et des paiements se fait aujourd'hui sur cahiers et fichiers Excel, ce qui entraîne :

- des retards de paiement non détectés à temps
- des adhérents qui s'entraînent sans abonnement à jour
- une absence de vision globale (effectif réel, fréquentation, chiffre d'affaires)
- des reçus manuels difficiles à retrouver

Phenix Club Manager centralise ces informations dans une application web accessible depuis l'accueil (ordinateur ou tablette) et depuis le bureau du gérant.

## 2. Objectifs

| Objectif | Indicateur de succès |
|---|---|
| Fiabiliser le suivi des adhérents | 100 % des inscrits enregistrés avec abonnement et statut |
| Contrôler l'accès à la salle | Pointage impossible sans abonnement à jour (hors tolérance) |
| Réduire les impayés | Liste des retards à jour chaque jour, rappels envoyés |
| Tracer les encaissements | Chaque paiement a un reçu numéroté et une période couverte |
| Piloter l'activité | Tableau de bord effectif, fréquentation, CA, taux d'impayés |

## 3. Acteurs

| Acteur | Rôle | Droits |
|---|---|---|
| Gérant | Pilote la salle | Accès complet : adhérents, pointage, paiements, statistiques, paramétrage des tarifs, abonnements, règles, gestion des comptes agents |
| Coach | Encadre les adhérents | Consulte les fiches et présences, propose, valide, refuse ou impose un programme d'entraînement ou d'alimentation |
| Agent d'accueil | Tient l'accueil | Inscriptions, modification des fiches, pointage, encaissement, consultation des retards, envoi des rappels |
| Visiteur du site web | Futur adhérent | Dépose une demande d'adhésion en ligne (si activé) |
| Adhérent (phase 2) | Membre de la salle | Consultation de son abonnement, de ses présences et de ses paiements |

## 4. Périmètre fonctionnel (phase 1)

### 4.1 Adhérents (inscrits)
- Deux canaux d'inscription : sur place (saisie par l'agent ou le gérant) et en ligne (formulaire sur le site de Phenix Club, activable par le gérant). Dans les deux cas la fiche est une demande d'adhésion en attente, validée à l'encaissement du premier paiement ou manuellement
- Création d'une fiche : nom, prénom, sexe, date de naissance, téléphone, email, adresse, photo, personne à contacter
- Attribution d'un code adhérent unique et d'un QR code
- Statut actif / inactif (calculé et modifiable manuellement)
- Recherche par nom, téléphone ou code ; filtres par statut, type d'abonnement, retard

### 4.2 Abonnements
- Catalogue de formules paramétrable par le gérant : nom, durée en jours, prix en FCFA, nombre de séances (optionnel)
- Souscription d'un abonnement à un adhérent : date de début, date de fin calculée
- Renouvellement : le nouvel abonnement démarre à la fin de l'ancien s'il est encore valide, sinon à la date choisie
- Historique des abonnements par adhérent

### 4.3 Présences (pointage)
- Pointage à l'arrivée : saisie manuelle (recherche adhérent) ou lecture du QR code
- Contrôle : refus si abonnement expiré au-delà de la tolérance
- Un seul pointage par adhérent et par jour (doublon refusé)
- Historique par adhérent et liste des présences du jour

### 4.4 Absences
- Détection des adhérents actifs sans pointage depuis N jours (N paramétrable)
- Liste des absents avec date de dernière présence
- Relance par SMS ou WhatsApp, trace de la relance

### 4.5 Paiements et frais d'inscription
- Frais d'inscription paramétrables (montant global, ajustable par formule), encaissés à la première adhésion et détaillés sur le reçu
- Chaque paiement est typé : frais d'inscription, abonnement, autre
- Enregistrement d'un règlement : montant, mode (espèces, mobile money, virement), référence, période couverte, abonnement lié
- Paiement partiel autorisé : un abonnement peut être réglé en plusieurs fois
- Reçu numéroté (format `RC-AAAA-000001`), imprimable / PDF
- Historique des paiements par adhérent et journal de caisse par jour

### 4.6 Retards
- Calcul automatique : abonnement dont l'échéance est dépassée de plus de X jours sans renouvellement ou solde impayé
- Liste des impayés avec montant dû et nombre de jours de retard
- Rappels SMS / WhatsApp avec trace

### 4.7 Tableau de bord
- Effectif actif, nouveaux inscrits du mois
- Fréquentation : présences du jour, de la semaine, du mois, courbe sur 30 jours
- Chiffre d'affaires du jour, du mois, par mode de paiement
- Taux d'impayés et montant total en retard

### 4.8 Paramétrage (gérant)
- Formules d'abonnement et tarifs
- Tolérance de pointage (jours), seuil de retard (jours), seuil d'absence (jours)
- Informations de la salle (nom, adresse, téléphone pour les reçus)
- Comptes utilisateurs

## 5. Hors périmètre phase 1

- Espace adhérent en ligne
- Paiement en ligne (mobile money intégré)
- Gestion des cours collectifs et des coachs
- Multi-salles

### 4.9 Caisse et comptabilité simplifiée
- Dépenses : saisie d'une sortie (catégorie paramétrable, libellé, montant, mode, bénéficiaire, justificatif photo)
- Journal de caisse par jour, semaine, mois : entrées par mode, sorties par catégorie, solde
- Vue "manquants" : abonnements avec solde dû, total attendu vs encaissé, retards
- Export Excel / PDF mensuel pour le comptable
- Pas de plan comptable ni de TVA : lisible par un gérant non comptable

### 4.10 Programmes d'entraînement et alimentation (phase 1 côté coach, phase 2 côté adhérent)
- Le coach ou le gérant crée un programme d'entraînement (jours, séances, exercices) ou d'alimentation (repas, conseils) pour un adhérent, imposé ou proposé
- Phase 2 : l'adhérent propose depuis son espace ses jours et son programme ; le coach ou le gérant valide, refuse avec motif, ou impose le sien
- L'adhérent voit toujours le programme en vigueur

### 4.11 Ergonomie
- Application fluide et intuitive : l'accueil doit pouvoir inscrire, pointer et encaisser en moins de trois écrans, sur tablette comme sur ordinateur
- Tableau de bord lisible d'un coup d'oeil pour le gérant (chiffres clés en haut, détails en dessous)
- Feedback immédiat (écran vert / orange / rouge au pointage, confirmation visuelle après encaissement)

## 6. Contraintes

| Contrainte | Choix |
|---|---|
| Langue | Interface en français |
| Monnaie | FCFA, montants entiers (pas de décimales) |
| Dates | Affichage jj/mm/aaaa, stockage ISO en base |
| Connexion | Application web, utilisable sur tablette à l'accueil |
| Sécurité | Authentification Sanctum, rôles gérant / agent, mots de passe hachés |
| Hébergement | o2switch (mutualisé, Passenger) ou VPS Hetzner |
| Sauvegarde | Dump PostgreSQL quotidien |

## 7. Livrables

1. Dossier d'analyse (ce dossier)
2. API Laravel documentée
3. Application React
4. Jeu de données de démonstration
5. Guide de déploiement
