# 06 - Flux métier

## 1. Inscription d'un adhérent

```mermaid
flowchart TD
    A[Agent : nouvel adhérent] --> B[Saisie fiche : nom, prénom, téléphone, photo...]
    B --> C{Téléphone déjà utilisé ?}
    C -- oui --> D[Erreur 422 : adhérent existant, proposer la fiche]
    C -- non --> E[Génération code PHX-xxxxxx et qr_token]
    E --> F[Création member, statut pending : demande d'adhésion]
    W[Visiteur : formulaire du site web] --> C
    F --> G{Souscrire un abonnement maintenant ?}
    G -- non --> H[Demande en attente, à valider en caisse]
    G -- oui --> I[Choix formule et date de début]
    I --> J[Création subscription, prix figé, end_date calculée]
    J --> K{Encaisser maintenant ?}
    K -- non --> L[Abonnement réservé, solde dû = prix, adhérent toujours pending]
    K -- oui --> M[Enregistrement paiement, reçu RC-AAAA-nnnnnn]
    M --> V[Validation automatique de l'adhésion : validated_at]
    V --> N[Statut adhérent recalculé : active]
    H -. validation manuelle agent / gérant .-> V
```

Règle appliquée : RG-ADH-05 et RG-ADH-06. Le visiteur du site web et la personne à l'accueil suivent le même circuit, seule la source diffère.

## 2. Pointage à l'arrivée

```mermaid
flowchart TD
    A[Adhérent se présente] --> B{Méthode}
    B -- QR --> C[Scan : qr_token]
    B -- manuel --> D[Recherche nom / téléphone / code]
    C --> E[Chargement adhérent + abonnement courant]
    D --> E
    E --> F0{Adhésion validée ?}
    F0 -- non --> X0[Refus : adhésion en attente, passer en caisse]
    F0 -- oui --> F{Adhérent suspendu ?}
    F -- oui --> X1[Refus : adhérent suspendu]
    F -- non --> G{Déjà pointé aujourd'hui ?}
    G -- oui --> X2[Refus : already_checked_in_today]
    G -- non --> H{Abonnement valide : start <= today <= end ?}
    H -- oui --> I{Séances restantes ?}
    I -- non --> X3[Refus : séances épuisées]
    I -- oui --> OK[Présence enregistrée, sessions_used +1]
    H -- non --> J{today <= end + tolérance ?}
    J -- oui --> W[Présence enregistrée avec avertissement : paiement attendu]
    J -- non --> K{Gérant force avec motif ?}
    K -- oui --> OKF[Présence enregistrée, forced = true]
    K -- non --> X4[Refus : abonnement expiré, rediriger vers paiement]
```

Règle appliquée : RG-PTG-01 à RG-PTG-06.

## 3. Encaissement d'un paiement

```mermaid
flowchart TD
    A[Agent : encaisser] --> B[Sélection adhérent]
    B --> C{Abonnement à régler ?}
    C -- oui, solde dû > 0 --> D[Montant proposé = balance_due]
    C -- non --> E[Paiement libre, période saisie]
    D --> F[Saisie montant, mode, référence]
    E --> F
    F --> G{Montant > solde dû ?}
    G -- oui --> X[Erreur 422 : montant supérieur au solde]
    G -- non --> H[Numéro de reçu séquentiel, verrou transaction]
    H --> I[Création payment]
    I --> J[Recalcul balance_due et statut adhérent]
    J --> K[Reçu PDF / impression]
```

Règle appliquée : RG-PAI-01 à RG-PAI-07.

## 4. Détection des retards et rappel

```mermaid
sequenceDiagram
    participant Cron as Tâche quotidienne 00:05
    participant SS as SubscriptionService
    participant DB as PostgreSQL
    participant G as Gérant / Agent
    participant LPS as LatePaymentService
    participant RS as ReminderService
    participant GW as Passerelle SMS / WhatsApp

    Cron->>SS: refreshStatuses()
    SS->>DB: pending -> active (start <= today)
    SS->>DB: active -> expired (end < today)
    SS->>DB: members.status recalculé
    G->>LPS: GET /api/late-payments
    LPS->>DB: requête RG-RET-01 (end + late_days < today, pas de renouvellement)
    DB-->>LPS: liste adhérents, days_late, amount_due
    LPS-->>G: JSON
    G->>RS: POST /api/reminders {member_id, type: late_payment, channel}
    RS->>DB: dernière relance même type < 7 jours ?
    alt relance récente
        RS-->>G: 422 reminder_too_recent
    else ok
        RS->>GW: envoi message
        GW-->>RS: statut
        RS->>DB: insert reminder (sent / failed)
        RS-->>G: 201
    end
```

## 5. Cycle de vie d'un abonnement

```mermaid
stateDiagram-v2
    [*] --> pending : souscription avec start_date future
    [*] --> active : souscription avec start_date <= today
    pending --> active : tâche quotidienne, start_date atteinte
    pending --> cancelled : annulation gérant
    active --> expired : end_date dépassée
    active --> cancelled : annulation gérant
    expired --> [*]
    cancelled --> [*]
```

Fenêtres associées à un abonnement `expired` :
- `end_date < today <= end_date + checkin_tolerance_days` : pointage accepté avec avertissement
- `today > end_date + late_payment_days` : adhérent en retard de paiement (si pas de renouvellement)

## 6. Statut de l'adhérent

```mermaid
stateDiagram-v2
    [*] --> pending : inscription sur place ou en ligne
    pending --> active : validation (1er paiement ou manuelle) avec abonnement valide
    pending --> inactive : validation sans abonnement valide
    inactive --> active : abonnement actif (ou en tolérance)
    active --> inactive : plus aucun abonnement valide
    active --> suspended : gérant
    inactive --> suspended : gérant
    pending --> suspended : gérant
    suspended --> active : gérant, si abonnement valide
    suspended --> inactive : gérant, sinon
    active --> pending : gérant, annulation de la validation
    inactive --> pending : gérant, annulation de la validation
```

`active` et `inactive` sont recalculés chaque nuit et à chaque souscription / paiement ; `pending` et `suspended` sont des statuts manuels, jamais recalculés.
