# 07 - User stories

Priorité : P1 indispensable au lancement, P2 phase 1 complète, P3 phase 2.

## Authentification

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-AUTH-01 | agent ou gérant | me connecter avec email et mot de passe | accéder à l'application | P1 |
| US-AUTH-02 | gérant | créer, désactiver un compte agent | contrôler qui accède à l'accueil | P2 |

## Adhérents

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-ADH-01 | agent | inscrire un nouvel adhérent avec sa photo | créer sa fiche et son QR code | P1 |
| US-ADH-02 | agent | rechercher un adhérent par nom, téléphone ou code | le retrouver en quelques secondes | P1 |
| US-ADH-03 | agent | voir la fiche complète (abonnement, présences, paiements, solde) | répondre à l'adhérent | P1 |
| US-ADH-04 | agent | modifier les coordonnées d'un adhérent | garder la fiche à jour | P1 |
| US-ADH-05 | gérant | suspendre ou réactiver un adhérent | bloquer un accès | P2 |
| US-ADH-06 | agent | imprimer la carte avec QR code | que l'adhérent pointe plus vite | P2 |
| US-ADH-07 | gérant | filtrer la liste par statut, formule, retard, absence | agir sur un segment | P2 |

## Abonnements

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-ABO-01 | gérant | créer et modifier les formules (nom, durée, prix, séances) | adapter l'offre | P1 |
| US-ABO-02 | agent | souscrire une formule à un adhérent | activer son accès | P1 |
| US-ABO-03 | agent | renouveler un abonnement en continuité de l'ancien | ne pas faire perdre de jours | P1 |
| US-ABO-04 | gérant | annuler un abonnement | corriger une erreur | P2 |
| US-ABO-05 | agent | voir l'historique des abonnements | retracer le parcours | P2 |

## Pointage et présences

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-PTG-01 | agent | pointer un adhérent par recherche manuelle | enregistrer sa présence | P1 |
| US-PTG-02 | agent | pointer en scannant le QR code | gagner du temps à l'accueil | P2 |
| US-PTG-03 | agent | être bloqué avec un message clair si l'abonnement n'est pas à jour | faire respecter la règle | P1 |
| US-PTG-04 | agent | voir un avertissement si l'adhérent est en tolérance | lui rappeler de payer | P1 |
| US-PTG-05 | agent | voir la liste des présents du jour | savoir qui est dans la salle | P1 |
| US-PTG-06 | gérant | forcer un pointage refusé avec motif | gérer une exception | P2 |

## Absences

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-ABS-01 | gérant | voir les adhérents actifs sans pointage depuis N jours | relancer avant qu'ils décrochent | P2 |
| US-ABS-02 | agent | envoyer une relance SMS ou WhatsApp depuis la liste | réactiver l'adhérent | P2 |

## Paiements

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-PAI-01 | agent | enregistrer un paiement (montant, mode, référence) | encaisser | P1 |
| US-PAI-02 | agent | générer et imprimer un reçu numéroté | remettre une preuve | P1 |
| US-PAI-03 | agent | enregistrer un paiement partiel | accepter un acompte | P2 |
| US-PAI-04 | gérant | annuler un paiement avec motif | corriger une erreur de saisie | P2 |
| US-PAI-05 | gérant | consulter le journal de caisse du jour par mode | faire la clôture | P1 |

## Retards

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-RET-01 | gérant | voir la liste des retards avec montant dû et jours de retard | recouvrer | P1 |
| US-RET-02 | agent | envoyer un rappel SMS ou WhatsApp | obtenir le paiement | P2 |
| US-RET-03 | gérant | régler le seuil de retard et la tolérance | adapter la politique | P1 |

## Tableau de bord

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-TDB-01 | gérant | voir l'effectif actif et les nouveaux inscrits du mois | suivre la croissance | P2 |
| US-TDB-02 | gérant | voir la fréquentation du jour, de la semaine, du mois et une courbe 30 jours | mesurer l'activité | P2 |
| US-TDB-03 | gérant | voir le chiffre d'affaires du jour et du mois par mode | suivre la trésorerie | P2 |
| US-TDB-04 | gérant | voir le taux et le montant des impayés | mesurer le risque | P2 |

## Phase 2 (adhérent)

| ID | En tant que | Je veux | Afin de | Priorité |
|---|---|---|---|---|
| US-MEM-01 | adhérent | consulter mon abonnement et sa date de fin | savoir quand payer | P3 |
| US-MEM-02 | adhérent | voir mes présences et mes reçus | suivre mon activité | P3 |
