# 08 - Plan des endpoints API

Préfixe : `/api`. Format : JSON. Authentification : `Authorization: Bearer <token>` (Sanctum) sauf `/login`.
Rôles : `manager` (gérant), `agent`. Les routes marquées [M] sont réservées au gérant.
Pagination : `?page=1&per_page=20`, réponse `{ data: [], meta: { current_page, last_page, per_page, total } }`.
Erreurs de validation : 422 `{ message, errors: { champ: [messages] } }`.

## Authentification

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| POST | /login | `{ email, password }` | 200 `{ token, user: { id, name, email, role } }` |
| POST | /logout | | 204 |
| GET | /me | | 200 `{ id, name, email, role }` |

## Adhérents

| Méthode | Route | Payload / Query | Réponse |
|---|---|---|---|
| GET | /members | `?search=&status=&source=&plan_id=&late=1&absent=1&page=` | 200 liste paginée de MemberResource (`status=pending` = demandes à valider) |
| POST | /members | `{ last_name, first_name, gender?, birth_date?, phone, email?, address?, emergency_contact_name?, emergency_contact_phone?, photo? (multipart), notes? }` | 201 MemberResource |
| GET | /members/{id} | | 200 MemberResource avec `current_subscription`, `balance_due`, `last_attendance_at`, `is_late` |
| PUT | /members/{id} | mêmes champs que POST | 200 MemberResource |
| DELETE | /members/{id} [M] | | 204 (soft delete) |
| PATCH | /members/{id}/status [M] | `{ status: "suspended" \| "active" \| "inactive" \| "pending", reason? }` | 200 MemberResource |
| POST | /members/{id}/validate | | 200 MemberResource, adhésion validée (RG-ADH-06) ; 422 `member_suspended` |
| POST | /public/registrations (sans auth, 10/h/IP) | `{ last_name, first_name, phone, gender?, birth_date?, email?, address?, subscription_plan_id? }` | 201 `{ message, code, status: "pending" }` ; 422 `online_registration_disabled` |
| GET | /members/{id}/subscriptions | | 200 liste SubscriptionResource |
| GET | /members/{id}/payments | | 200 liste PaymentResource |
| GET | /members/{id}/attendances | `?from=&to=` | 200 liste AttendanceResource |
| GET | /members/{id}/qr | | 200 image PNG du QR code |
| GET | /members/by-qr/{token} | | 200 MemberResource (utilisé par le scan) |

MemberResource :
```json
{
  "id": 1, "code": "PHX-000001", "last_name": "Mbemba", "first_name": "Alain",
  "gender": "M", "birth_date": "1995-03-12", "phone": "+242061234567", "email": null,
  "address": "Moungali", "photo_url": "https://.../storage/members/1.jpg",
  "status": "active", "registration_source": "on_site", "validated_at": "2026-01-15T10:00:00+01:00", "joined_at": "2026-01-15",
  "current_subscription": { "id": 10, "plan": "Mensuel", "start_date": "2026-11-01", "end_date": "2026-11-30", "status": "active", "balance_due": 0 },
  "last_attendance_at": "2026-11-01T18:32:00+01:00",
  "is_late": false, "days_late": 0, "amount_due": 0
}
```

## Formules d'abonnement

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| GET | /subscription-plans | `?active=1` | 200 liste |
| POST | /subscription-plans [M] | `{ name, duration_days, price, sessions_count?, description?, is_active? }` | 201 |
| PUT | /subscription-plans/{id} [M] | idem | 200 |
| DELETE | /subscription-plans/{id} [M] | | 204 si aucun abonnement lié, sinon 422 |

## Abonnements

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| POST | /subscriptions | `{ member_id, subscription_plan_id, start_date? }` | 201 SubscriptionResource (`start_date` par défaut : lendemain de la fin de l'abonnement courant ou aujourd'hui, RG-ABO-03) |
| GET | /subscriptions/{id} | | 200 |
| PATCH | /subscriptions/{id}/cancel [M] | `{ reason }` | 200 |

SubscriptionResource :
```json
{
  "id": 10, "member_id": 1, "plan": { "id": 1, "name": "Mensuel" },
  "start_date": "2026-11-01", "end_date": "2026-11-30",
  "price_at_subscription": 15000, "amount_paid": 10000, "balance_due": 5000,
  "sessions_count": null, "sessions_used": 8, "status": "active"
}
```

## Pointage

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| POST | /attendances | `{ member_id }` ou `{ qr_token }`, `method: "manual" \| "qr"`, `force?: true, force_reason?` [M si force] | 201 `{ attendance, warning: null \| "Abonnement expiré depuis 2 jours, tolérance jusqu'au 03/11/2026" }` |
| GET | /attendances | `?date=2026-11-01` (défaut aujourd'hui) | 200 liste du jour avec adhérent |
| DELETE | /attendances/{id} [M] | | 204 |

Erreurs 422 (`error` dans la réponse) : `member_suspended`, `already_checked_in_today`, `no_valid_subscription`, `subscription_expired`, `sessions_exhausted`.

## Paiements

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| GET | /payments | `?date=&from=&to=&method=&member_id=&page=` | 200 liste paginée + `meta.totals: { cash, mobile_money, bank_transfer, total }` |
| POST | /payments | `{ member_id, subscription_id?, amount, method, reference?, paid_at?, period_start?, period_end?, notes? }` | 201 PaymentResource |
| GET | /payments/{id} | | 200 |
| GET | /payments/{id}/receipt?format=json\|pdf | | 200 reçu JSON (défaut) ou PDF, 422 `pdf_unavailable` si dompdf absent |
| PATCH | /payments/{id}/cancel [M] | `{ reason }` | 200 |

PaymentResource :
```json
{
  "id": 55, "receipt_number": "RC-2026-000055", "member": { "id": 1, "code": "PHX-000001", "full_name": "Alain Mbemba" },
  "subscription_id": 10, "amount": 10000, "method": "mobile_money", "reference": "MP261101.1832.A12345",
  "paid_at": "2026-11-01T18:32:00+01:00", "period_start": "2026-11-01", "period_end": "2026-11-30",
  "received_by": { "id": 2, "name": "Accueil" }, "cancelled_at": null
}
```

## Retards et absences

| Méthode | Route | Query | Réponse |
|---|---|---|---|
| GET | /late-payments | `?min_days=&page=` | 200 `[{ member, end_date, days_late, amount_due, last_reminder_at }]` |
| GET | /absences | `?days=` (défaut `inactivity_days`) | 200 `[{ member, last_attendance_at, days_absent, last_reminder_at }]` |

## Relances

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| POST | /reminders | `{ member_id, type: "absence" \| "late_payment" \| "expiry_soon", channel: "sms" \| "whatsapp", message? }` | 201 ReminderResource ; 422 `reminder_too_recent` |
| GET | /members/{id}/reminders | | 200 liste |

## Tableau de bord [M]

| Méthode | Route | Query | Réponse |
|---|---|---|---|
| GET | /dashboard | `?month=2026-11` | voir ci-dessous |

```json
{
  "members": { "active": 142, "inactive": 38, "suspended": 2, "new_this_month": 11 },
  "attendance": { "today": 37, "this_week": 210, "this_month": 820, "last_30_days": [ { "date": "2026-10-03", "count": 25 } ] },
  "revenue": { "today": 45000, "this_month": 1250000, "by_method": { "cash": 800000, "mobile_money": 400000, "bank_transfer": 50000 } },
  "late_payments": { "count": 17, "amount": 255000, "rate": 0.107 }
}
```

## Caisse

| Méthode | Route | Payload / Query | Réponse |
|---|---|---|---|
| GET | /expense-categories | `?active=1` | 200 liste |
| POST / PUT / DELETE | /expense-categories/{id} [M] | `{ name, is_active?, sort_order? }` | 201 / 200 / 204 (422 `category_in_use`) |
| GET | /expenses | `?from=&to=&category_id=&method=&page=` | 200 liste paginée + `meta.totals` |
| POST | /expenses | `{ expense_category_id, label, amount, method, payee?, reference?, spent_on, notes?, receipt? (multipart) }` | 201 |
| PATCH | /expenses/{id}/cancel [M] | `{ reason }` | 200 |
| GET | /cashbook | `?from=&to=` (défaut mois en cours) | 200 `{ period, income: { total, by_method, by_type }, expenses: { total, by_category, by_method }, balance, year_to_date_balance, outstanding: { expected, collected, missing, recovery_rate, late_count, late_amount }, days: [ { date, income, expenses } ] }` |
| GET | /cashbook/export?from=&to= [M] | | fichier CSV |

## Programmes

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| GET | /members/{id}/programs | `?kind=` | 200 liste |
| POST | /members/{id}/programs [coach, M] | `{ kind, title, starts_on?, ends_on?, content, notes?, impose?: bool }` | 201 (status `imposed` si impose, sinon `proposed`) |
| GET | /programs/{id} | | 200 |
| PUT | /programs/{id} [coach, M] | idem | 200 |
| PATCH | /programs/{id}/approve [coach, M] | `{ note? }` | 200 ; archive le programme effectif précédent du même type |
| PATCH | /programs/{id}/decline [coach, M] | `{ note }` | 200 |
| PATCH | /programs/{id}/archive [coach, M] | | 200 |
| GET | /programs?status=proposed | coach, M | 200 demandes à traiter |

## Paramètres et utilisateurs [M]

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| GET | /settings | | 200 `{ checkin_tolerance_days: 3, late_payment_days: 5, inactivity_days: 14, ... }` |
| PUT | /settings | `{ key: value, ... }` | 200 |
| GET | /users | | 200 liste |
| POST | /users | `{ name, email, password, role }` | 201 |
| PUT | /users/{id} | `{ name, email, password?, role, is_active }` | 200 |


## Application adhérent (`/api/member`, token d'adhérent, refusé sur les routes du personnel)

| Méthode | Route | Payload | Réponse |
|---|---|---|---|
| POST | /member/login | `{ phone, password, device_name? }` (téléphone sous tout format) | `{ token, member }` |
| POST | /member/logout | | 204 |
| PUT | /member/password | `{ current_password, password, password_confirmation }` | 200, autres appareils déconnectés |
| GET | /member/me | | accueil : profil, abonnement, accès du jour, solde, statistiques, club |
| GET | /member/balance | | `{ total_due, items[], late }` |
| GET | /member/subscriptions | | abonnements non annulés |
| GET | /member/payments?page= | | paiements paginés |
| GET | /member/payments/{id}/receipt | | ReceiptResource (404 si ce n'est pas le sien) |
| GET | /member/attendances?page= | | présences paginées |
| GET | /member/programs | | programmes en vigueur et proposés (sans notes internes) |
| GET | /member/plans | | formules actives et coordonnées du club |
| POST | /member/check-in | `{ code }` (QR de la borne) | 201 ; 422 `invalid_checkin_code` ou code de refus du pointage |

Personnel : `GET /kiosk/token` [agent, M] (QR de la borne, `{ payload, period, expires_in }`), `POST /members/{id}/app-access` [agent, M] (`{ code }` à 6 chiffres, affiché une fois), `DELETE /members/{id}/app-access` [agent, M].
