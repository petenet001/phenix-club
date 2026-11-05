# 03 - Modèle conceptuel de données (MCD)

## 1. Entités

| Entité | Description |
|---|---|
| UTILISATEUR | Compte de connexion : gérant ou agent d'accueil |
| ADHERENT | Personne inscrite à la salle |
| FORMULE | Type d'abonnement du catalogue (mensuel, trimestriel, 10 séances...) |
| ABONNEMENT | Souscription d'une formule par un adhérent sur une période |
| PAIEMENT | Règlement encaissé, avec reçu |
| PRESENCE | Pointage d'un adhérent à une date et heure |
| RELANCE | Message SMS / WhatsApp envoyé à un adhérent |
| PARAMETRE | Valeur de configuration (tolérance, seuils, infos du club) |

## 2. Associations

| Association | Entités | Cardinalités | Lecture |
|---|---|---|---|
| souscrire | ADHERENT - ABONNEMENT | 1,n - 1,1 | Un adhérent souscrit 0 ou n abonnements ; un abonnement concerne un seul adhérent |
| correspondre | FORMULE - ABONNEMENT | 0,n - 1,1 | Une formule sert à 0 ou n abonnements ; un abonnement a une seule formule |
| régler | ADHERENT - PAIEMENT | 0,n - 1,1 | Un adhérent effectue 0 ou n paiements |
| couvrir | ABONNEMENT - PAIEMENT | 0,n - 0,1 | Un abonnement est couvert par 0 ou n paiements ; un paiement couvre au plus un abonnement |
| pointer | ADHERENT - PRESENCE | 0,n - 1,1 | Un adhérent a 0 ou n présences |
| utiliser | ABONNEMENT - PRESENCE | 0,n - 0,1 | Une présence consomme au plus un abonnement |
| relancer | ADHERENT - RELANCE | 0,n - 1,1 | Un adhérent reçoit 0 ou n relances |
| enregistrer | UTILISATEUR - ABONNEMENT / PAIEMENT / PRESENCE / RELANCE | 0,n - 0,1 | L'utilisateur qui a saisi l'opération |

## 3. Diagramme

```mermaid
erDiagram
    UTILISATEUR {
        string nom
        string email
        string role "gerant | agent"
        bool actif
    }
    ADHERENT {
        string code "PHX-000001"
        string nom
        string prenom
        string sexe
        date date_naissance
        string telephone
        string email
        string adresse
        string photo
        string qr_token
        string statut "active | inactive | suspended"
        date date_inscription
    }
    FORMULE {
        string nom
        int duree_jours
        int prix_fcfa
        int nb_seances "optionnel"
        bool actif
    }
    ABONNEMENT {
        date date_debut
        date date_fin
        int prix_souscription
        int seances_utilisees
        string statut "pending | active | expired | cancelled"
    }
    PAIEMENT {
        string numero_recu "RC-2026-000001"
        int montant_fcfa
        string mode "cash | mobile_money | bank_transfer"
        string reference
        datetime date_paiement
        date periode_debut
        date periode_fin
        datetime annule_le
    }
    PRESENCE {
        datetime pointe_le
        string methode "manual | qr"
        bool force
        string motif
    }
    RELANCE {
        string type "absence | late_payment | expiry_soon"
        string canal "sms | whatsapp"
        text message
        string statut "sent | failed"
        datetime envoye_le
    }
    PARAMETRE {
        string cle
        string valeur
    }

    ADHERENT ||--o{ ABONNEMENT : souscrit
    FORMULE ||--o{ ABONNEMENT : correspond
    ADHERENT ||--o{ PAIEMENT : regle
    ABONNEMENT |o--o{ PAIEMENT : couvre
    ADHERENT ||--o{ PRESENCE : pointe
    ABONNEMENT |o--o{ PRESENCE : utilise
    ADHERENT ||--o{ RELANCE : recoit
    UTILISATEUR |o--o{ ABONNEMENT : enregistre
    UTILISATEUR |o--o{ PAIEMENT : encaisse
    UTILISATEUR |o--o{ PRESENCE : saisit
    UTILISATEUR |o--o{ RELANCE : envoie
```

## 4. Choix de modélisation

- Le retard de paiement et l'absence ne sont pas des entités : ce sont des états calculés à partir d'ABONNEMENT, PAIEMENT et PRESENCE (voir règles RG-RET et RG-ABS). Seules les RELANCES sont persistées.
- Le prix est recopié dans ABONNEMENT au moment de la souscription pour que l'historique reste exact si le tarif change.
- PAIEMENT porte sa propre période couverte, ce qui permet les paiements hors abonnement (frais d'inscription, produits) et les acomptes.
- Le statut de l'adhérent est dérivé de ses abonnements, mais stocké pour permettre les filtres rapides et la suspension manuelle. Il est recalculé par la tâche quotidienne et à chaque souscription / paiement.
- PARAMETRE est une table clé / valeur simple, suffisante pour une dizaine de réglages.
