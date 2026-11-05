# 02 - Règles de gestion

Toutes les durées sont en jours. Les paramètres marqués (P) sont modifiables par le gérant dans la table `settings`.

## 1. Paramètres (table settings)

| Clé | Valeur par défaut | Description |
|---|---|---|
| `checkin_tolerance_days` (P) | 3 | Jours de tolérance après l'échéance pendant lesquels le pointage reste autorisé |
| `late_payment_days` (P) | 5 | Jours après l'échéance à partir desquels un adhérent est en retard de paiement |
| `inactivity_days` (P) | 14 | Jours sans pointage à partir desquels un adhérent actif est considéré absent |
| `receipt_prefix` (P) | RC | Préfixe des numéros de reçu |
| `club_name` (P) | Phenix Club | Nom affiché sur les reçus |
| `club_phone` (P) | | Téléphone affiché sur les reçus |
| `club_address` (P) | Brazzaville | Adresse affichée sur les reçus |
| `online_registration_enabled` (P) | faux | Autorise les demandes d'adhésion depuis le site web |
| `registration_fee` (P) | 5000 | Frais d'inscription en FCFA (une formule peut définir son propre montant) |

## 2. Adhérent

- RG-ADH-01 : le code adhérent est unique, généré automatiquement au format `PHX-000001`.
- RG-ADH-02 : le téléphone est obligatoire et unique (clé de recherche à l'accueil et canal de rappel).
- RG-ADH-03 : pour un adhérent validé, le statut `active` est vrai s'il possède un abonnement en cours (date du jour comprise entre début et fin + tolérance), sinon `inactive`. Ces deux statuts sont recalculés automatiquement. Le gérant peut forcer `suspended` (adhérent bloqué quelle que soit sa situation).
- RG-ADH-04 : un adhérent n'est jamais supprimé physiquement (soft delete) pour conserver l'historique des paiements.
- RG-ADH-05 : toute inscription, sur place (saisie par l'agent ou le gérant) ou en ligne (formulaire du site web), crée une **demande d'adhésion** au statut `pending`. La source est tracée (`registration_source` : `on_site` / `online`). Un adhérent `pending` peut réserver un abonnement mais ne peut pas pointer et n'apparaît ni dans les retards ni dans les absences.
- RG-ADH-06 : la demande est validée (`validated_at`, `validated_by`) soit automatiquement à l'encaissement du premier paiement (UC-07), soit manuellement par un agent ou le gérant (bouton "Valider l'adhésion"). À la validation, le statut devient `active` ou `inactive` selon RG-ADH-03. Le gérant peut remettre un adhérent en `pending` (annule la validation).
- RG-ADH-07 : l'inscription en ligne n'est acceptée que si `online_registration_enabled` est vrai ; elle est limitée à 10 demandes par heure et par adresse IP et ne renvoie jamais le QR token (remis sur place après validation).

## 3. Abonnement

- RG-ABO-01 : `end_date = start_date + duration_days - 1` (un abonnement de 30 jours débutant le 01/11 se termine le 30/11).
- RG-ABO-02 : le prix est copié de la formule au moment de la souscription (`price_at_subscription`), un changement de tarif ultérieur n'affecte pas les abonnements existants.
- RG-ABO-03 : renouvellement : si l'adhérent a un abonnement encore valide, le nouveau démarre le lendemain de la fin de l'ancien. Sinon il démarre à la date saisie (par défaut aujourd'hui).
- RG-ABO-04 : un adhérent ne peut avoir qu'un seul abonnement au statut `active` à la fois ; les suivants sont `pending` jusqu'à leur date de début.
- RG-ABO-05 : statuts : `pending` (débute dans le futur), `active`, `expired` (end_date dépassée), `cancelled` (annulé par le gérant, non remboursé par défaut).
- RG-ABO-06 : `amount_paid` = somme des paiements liés ; `balance_due = price_at_subscription - amount_paid`. Un abonnement est "soldé" quand `balance_due = 0`.
- RG-ABO-07 : une tâche planifiée quotidienne (`subscriptions:refresh-status`) passe les abonnements en `active` ou `expired` selon la date du jour.

## 4. Pointage

- RG-PTG-01 : le pointage est autorisé si l'adhérent a un abonnement dont `start_date <= aujourd'hui <= end_date + checkin_tolerance_days` et dont le statut n'est pas `cancelled`, et si l'adhérent n'est ni `suspended` ni `pending` (adhésion non validée).
- RG-PTG-02 : si l'abonnement est dans la fenêtre de tolérance (échéance dépassée mais tolérance non épuisée), le pointage est accepté avec un avertissement affiché à l'accueil (`warning: "Abonnement expiré depuis N jours, tolérance jusqu'au jj/mm/aaaa"`).
- RG-PTG-03 : un seul pointage par adhérent et par jour calendaire. Un second pointage renvoie une erreur 422 `already_checked_in_today`.
- RG-PTG-04 : le pointage enregistre l'heure exacte, la méthode (`manual` ou `qr`), l'agent qui l'a saisi et l'abonnement utilisé.
- RG-PTG-05 : si la formule a un nombre de séances limité (`sessions_count`), le pointage est refusé quand `sessions_used >= sessions_count`.
- RG-PTG-06 : le gérant peut forcer un pointage refusé (`force: true`), la raison est enregistrée.

Cas limites à tester :
- pointage le jour exact de l'échéance (autorisé)
- pointage le lendemain de la fin de tolérance (refusé)
- deux pointages le même jour (second refusé)
- pointage à 23h59 puis 00h01 (deux jours différents, autorisé)
- adhérent avec abonnement `pending` qui démarre demain (refusé aujourd'hui)
- adhérent suspendu avec abonnement valide (refusé)
- adhérent `pending` avec abonnement réservé mais non payé (refusé : "Adhésion à valider, passer en caisse")
- changement de `checkin_tolerance_days` : s'applique immédiatement aux pointages suivants

## 5. Paiement

- RG-PAI-01 : un paiement est rattaché à un adhérent et, en général, à un abonnement. Un paiement sans abonnement est possible (ex : vente de carte, frais d'inscription).
- RG-PAI-02 : le montant est un entier positif en FCFA.
- RG-PAI-03 : le numéro de reçu est unique, séquentiel par année : `RC-2026-000001`.
- RG-PAI-04 : modes de paiement : `cash`, `mobile_money` (référence de transaction attendue), `bank_transfer` (référence attendue).
- RG-PAI-05 : la période couverte (`period_start`, `period_end`) est reprise de l'abonnement par défaut, modifiable.
- RG-PAI-06 : un paiement ne peut pas dépasser le solde restant de l'abonnement (`balance_due`). Un dépassement renvoie 422.
- RG-PAI-07 : un paiement enregistré n'est jamais supprimé ; il peut être annulé par le gérant (`cancelled_at`, `cancel_reason`), ce qui recrédite le solde.
- RG-PAI-08 : chaque paiement a un type : `registration` (frais d'inscription), `subscription` (abonnement), `other` (produit, service). Les frais d'inscription = `registration_fee` de la formule si défini, sinon le paramètre global. Ils sont dus une seule fois, à la première adhésion ; l'écran d'encaissement les propose automatiquement tant qu'aucun paiement `registration` non annulé n'existe pour l'adhérent.
- RG-PAI-09 : le premier paiement non annulé (quel que soit son type) valide l'adhésion si elle est `pending` (RG-ADH-06).

## 5 bis. Caisse et dépenses

- RG-CAI-01 : une dépense a une catégorie (table paramétrable), un libellé, un montant entier FCFA > 0, un mode (espèces, mobile money, virement), une date, un bénéficiaire et un justificatif optionnels, l'utilisateur qui l'a saisie.
- RG-CAI-02 : saisie par agent ou gérant ; annulation (avec motif) réservée au gérant ; jamais de suppression physique.
- RG-CAI-03 : journal de caisse sur une période = entrées (paiements non annulés, par mode et par type) - sorties (dépenses non annulées, par catégorie et par mode) = solde de la période. Le solde cumulé de l'année est affiché à titre indicatif.
- RG-CAI-04 : "manquants" = somme des `balance_due` des abonnements non annulés dont la période est commencée ; "attendu" = prix de ces abonnements ; taux de recouvrement = encaissé / attendu.
- RG-CAI-05 : une catégorie utilisée ne peut pas être supprimée, seulement désactivée.

## 8 bis. Programmes d'entraînement et d'alimentation

- RG-PRG-01 : un programme appartient à un adhérent, a un type (`training` ou `nutrition`), un titre, une période optionnelle et un contenu structuré (JSON : jours, séances, exercices ou repas).
- RG-PRG-02 : auteur : coach ou gérant (phase 1) ou l'adhérent lui-même depuis son espace (phase 2, `proposed_by_member`).
- RG-PRG-03 : statuts : `proposed` (en attente d'avis), `approved` (validé par coach ou gérant), `declined` (refusé avec motif), `imposed` (créé et appliqué directement par coach ou gérant), `archived`.
- RG-PRG-04 : un seul programme effectif (`approved` ou `imposed`) par adhérent et par type à la fois ; en valider ou imposer un nouveau archive le précédent.
- RG-PRG-05 : seuls le coach et le gérant valident, refusent ou imposent. L'agent d'accueil consulte seulement.
- RG-PRG-06 : les jours prévus du programme d'entraînement servent de référence pour le suivi d'assiduité (présences réelles vs prévues), affiché sur la fiche adhérent.

Cas limites à tester :
- paiement partiel puis solde exact (balance_due = 0)
- paiement supérieur au solde (refusé)
- annulation d'un paiement : balance_due remonte, retard recalculé
- numérotation de reçu au changement d'année (RC-2027-000001)
- deux encaissements simultanés par deux agents (unicité du numéro garantie en base)

## 6. Retard de paiement

- RG-RET-01 : un adhérent est en retard si l'une des conditions est vraie :
  1. son dernier abonnement non annulé a `end_date + late_payment_days < aujourd'hui` et il n'a aucun abonnement `active` ou `pending`
  2. il a un abonnement `active` ou `expired` avec `balance_due > 0` et `end_date + late_payment_days < aujourd'hui`
- RG-RET-02 : `days_late = aujourd'hui - end_date - late_payment_days`.
- RG-RET-03 : montant dû = `balance_due` si solde impayé, sinon prix de la dernière formule (renouvellement attendu).
- RG-RET-04 : le retard est calculé à la volée (requête), il n'est pas stocké. Seules les relances sont stockées.
- RG-RET-05 : un adhérent `suspended`, `pending` ou supprimé n'apparaît pas dans les retards.

Cas limites à tester :
- adhérent dont l'échéance est à J+late_payment_days exactement (pas encore en retard)
- adhérent qui renouvelle en pending alors que l'ancien est expiré depuis 10 jours (plus en retard)
- adhérent avec abonnement soldé expiré et aucun renouvellement (retard = renouvellement attendu)

## 7. Absence

- RG-ABS-01 : un adhérent est absent s'il est `active` et que sa dernière présence date de plus de `inactivity_days` jours (ou qu'il n'a jamais pointé depuis le début de son abonnement courant et que celui-ci a plus de `inactivity_days` jours).
- RG-ABS-02 : les adhérents en retard de paiement ne sont pas comptés dans les absences (traités dans le module retards).

## 8. Relances et rappels

- RG-REL-01 : types : `absence`, `late_payment`, `expiry_soon` (échéance dans 3 jours).
- RG-REL-02 : canaux : `sms`, `whatsapp`. Le message est généré depuis un modèle avec le prénom, le montant et la date d'échéance.
- RG-REL-03 : chaque envoi est tracé (adhérent, type, canal, message, agent, date, statut `sent` / `failed`).
- RG-REL-04 : pas plus d'une relance du même type par adhérent et par 7 jours (évite le spam).

## 9. Tableau de bord

- Effectif actif = adhérents au statut `active` (RG-ADH-03)
- Fréquentation = nombre de pointages sur la période
- Chiffre d'affaires = somme des paiements non annulés sur la période (`paid_at`)
- Taux d'impayés = adhérents en retard / (adhérents actifs + adhérents en retard)
