-- Phenix Club Manager - schéma PostgreSQL
-- Équivalent SQL des migrations Laravel (utile pour créer la base sans artisan, ou pour un DBA).
-- Les tables Laravel (personal_access_tokens, cache, jobs, sessions...) sont créées par `php artisan migrate`.

BEGIN;

CREATE TABLE IF NOT EXISTS users (
    id                  BIGSERIAL PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at   TIMESTAMP NULL,
    password            VARCHAR(255) NOT NULL,
    role                VARCHAR(20) NOT NULL DEFAULT 'agent',
    is_active           BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token      VARCHAR(100) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    CONSTRAINT users_role_check CHECK (role IN ('manager', 'agent', 'coach'))
);

CREATE TABLE subscription_plans (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    duration_days   INTEGER NOT NULL,
    price           INTEGER NOT NULL,
    sessions_count  INTEGER NULL,
    registration_fee INTEGER NULL,
    description     TEXT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT subscription_plans_duration_check CHECK (duration_days > 0)
);
COMMENT ON COLUMN subscription_plans.price IS 'Prix en FCFA';
COMMENT ON COLUMN subscription_plans.sessions_count IS 'Null = illimité';

CREATE TABLE members (
    id                      BIGSERIAL PRIMARY KEY,
    code                    VARCHAR(20) NOT NULL UNIQUE,
    last_name               VARCHAR(100) NOT NULL,
    first_name              VARCHAR(100) NOT NULL,
    gender                  CHAR(1) NULL,
    birth_date              DATE NULL,
    phone                   VARCHAR(20) NOT NULL UNIQUE,
    email                   VARCHAR(150) NULL,
    address                 VARCHAR(255) NULL,
    photo_path              VARCHAR(255) NULL,
    emergency_contact_name  VARCHAR(100) NULL,
    emergency_contact_phone VARCHAR(20) NULL,
    qr_token                UUID NOT NULL UNIQUE,
    status                  VARCHAR(20) NOT NULL DEFAULT 'pending',
    registration_source     VARCHAR(10) NOT NULL DEFAULT 'on_site',
    validated_at            TIMESTAMP NULL,
    validated_by            BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    joined_at               DATE NOT NULL,
    notes                   TEXT NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    deleted_at              TIMESTAMP NULL,
    CONSTRAINT members_gender_check CHECK (gender IS NULL OR gender IN ('M', 'F')),
    CONSTRAINT members_status_check CHECK (status IN ('pending', 'active', 'inactive', 'suspended')),
    CONSTRAINT members_registration_source_check CHECK (registration_source IN ('on_site', 'online'))
);
CREATE INDEX members_last_name_first_name_index ON members (last_name, first_name);
CREATE INDEX members_status_index ON members (status);
COMMENT ON COLUMN members.code IS 'PHX-000001';

CREATE TABLE subscriptions (
    id                      BIGSERIAL PRIMARY KEY,
    member_id               BIGINT NOT NULL REFERENCES members (id) ON DELETE CASCADE,
    subscription_plan_id    BIGINT NOT NULL REFERENCES subscription_plans (id) ON DELETE RESTRICT,
    start_date              DATE NOT NULL,
    end_date                DATE NOT NULL,
    price_at_subscription   INTEGER NOT NULL,
    sessions_used           INTEGER NOT NULL DEFAULT 0,
    status                  VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_by              BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    cancelled_at            TIMESTAMP NULL,
    cancel_reason           VARCHAR(255) NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    CONSTRAINT subscriptions_dates_check CHECK (end_date >= start_date),
    CONSTRAINT subscriptions_status_check CHECK (status IN ('pending', 'active', 'expired', 'cancelled'))
);
CREATE INDEX subscriptions_member_id_status_index ON subscriptions (member_id, status);
CREATE INDEX subscriptions_end_date_index ON subscriptions (end_date);
COMMENT ON COLUMN subscriptions.price_at_subscription IS 'Prix figé en FCFA';

CREATE TABLE payments (
    id              BIGSERIAL PRIMARY KEY,
    receipt_number  VARCHAR(20) NOT NULL UNIQUE,
    member_id       BIGINT NOT NULL REFERENCES members (id) ON DELETE RESTRICT,
    subscription_id BIGINT NULL REFERENCES subscriptions (id) ON DELETE SET NULL,
    type            VARCHAR(20) NOT NULL DEFAULT 'subscription',
    amount          INTEGER NOT NULL,
    method          VARCHAR(20) NOT NULL,
    reference       VARCHAR(100) NULL,
    paid_at         TIMESTAMP NOT NULL,
    period_start    DATE NULL,
    period_end      DATE NULL,
    received_by     BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    notes           TEXT NULL,
    cancelled_at    TIMESTAMP NULL,
    cancel_reason   VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT payments_amount_check CHECK (amount > 0),
    CONSTRAINT payments_type_check CHECK (type IN ('registration', 'subscription', 'other')),
    CONSTRAINT payments_method_check CHECK (method IN ('cash', 'mobile_money', 'bank_transfer')),
    CONSTRAINT payments_period_check CHECK (period_end IS NULL OR period_start IS NULL OR period_end >= period_start)
);
CREATE INDEX payments_paid_at_index ON payments (paid_at);
CREATE INDEX payments_member_id_paid_at_index ON payments (member_id, paid_at);
COMMENT ON COLUMN payments.receipt_number IS 'RC-2026-000001';
COMMENT ON COLUMN payments.amount IS 'Montant en FCFA';

CREATE TABLE attendances (
    id              BIGSERIAL PRIMARY KEY,
    member_id       BIGINT NOT NULL REFERENCES members (id) ON DELETE CASCADE,
    subscription_id BIGINT NULL REFERENCES subscriptions (id) ON DELETE SET NULL,
    attended_on     DATE NOT NULL,
    checked_in_at   TIMESTAMP NOT NULL,
    method          VARCHAR(10) NOT NULL DEFAULT 'manual',
    forced          BOOLEAN NOT NULL DEFAULT FALSE,
    force_reason    VARCHAR(255) NULL,
    recorded_by     BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT attendances_member_id_attended_on_unique UNIQUE (member_id, attended_on),
    CONSTRAINT attendances_method_check CHECK (method IN ('manual', 'qr'))
);
CREATE INDEX attendances_attended_on_index ON attendances (attended_on);

CREATE TABLE reminders (
    id          BIGSERIAL PRIMARY KEY,
    member_id   BIGINT NOT NULL REFERENCES members (id) ON DELETE CASCADE,
    type        VARCHAR(20) NOT NULL,
    channel     VARCHAR(10) NOT NULL,
    message     TEXT NOT NULL,
    status      VARCHAR(10) NOT NULL DEFAULT 'sent',
    error       VARCHAR(255) NULL,
    sent_at     TIMESTAMP NOT NULL,
    sent_by     BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    CONSTRAINT reminders_type_check CHECK (type IN ('absence', 'late_payment', 'expiry_soon')),
    CONSTRAINT reminders_channel_check CHECK (channel IN ('sms', 'whatsapp')),
    CONSTRAINT reminders_status_check CHECK (status IN ('sent', 'failed'))
);
CREATE INDEX reminders_member_id_type_sent_at_index ON reminders (member_id, type, sent_at);

CREATE TABLE expense_categories (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

CREATE TABLE expenses (
    id                  BIGSERIAL PRIMARY KEY,
    expense_category_id BIGINT NOT NULL REFERENCES expense_categories (id) ON DELETE RESTRICT,
    label               VARCHAR(150) NOT NULL,
    amount              INTEGER NOT NULL,
    method              VARCHAR(20) NOT NULL,
    payee               VARCHAR(150) NULL,
    reference           VARCHAR(100) NULL,
    spent_on            DATE NOT NULL,
    receipt_path        VARCHAR(255) NULL,
    notes               TEXT NULL,
    recorded_by         BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    cancelled_at        TIMESTAMP NULL,
    cancel_reason       VARCHAR(255) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    CONSTRAINT expenses_amount_check CHECK (amount > 0),
    CONSTRAINT expenses_method_check CHECK (method IN ('cash', 'mobile_money', 'bank_transfer'))
);
CREATE INDEX expenses_spent_on_index ON expenses (spent_on);

CREATE TABLE training_programs (
    id                  BIGSERIAL PRIMARY KEY,
    member_id           BIGINT NOT NULL REFERENCES members (id) ON DELETE CASCADE,
    kind                VARCHAR(20) NOT NULL,
    title               VARCHAR(150) NOT NULL,
    status              VARCHAR(20) NOT NULL DEFAULT 'proposed',
    proposed_by_member  BOOLEAN NOT NULL DEFAULT FALSE,
    author_id           BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    starts_on           DATE NULL,
    ends_on             DATE NULL,
    content             JSONB NOT NULL DEFAULT '{}'::jsonb,
    notes               TEXT NULL,
    reviewed_by         BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    reviewed_at         TIMESTAMP NULL,
    review_note         VARCHAR(255) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    CONSTRAINT training_programs_kind_check CHECK (kind IN ('training', 'nutrition')),
    CONSTRAINT training_programs_status_check CHECK (status IN ('proposed', 'approved', 'declined', 'imposed', 'archived'))
);
CREATE INDEX training_programs_member_id_status_index ON training_programs (member_id, status);

CREATE TABLE settings (
    id          BIGSERIAL PRIMARY KEY,
    key         VARCHAR(50) NOT NULL UNIQUE,
    value       TEXT NULL,
    type        VARCHAR(10) NOT NULL DEFAULT 'string',
    label       VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    CONSTRAINT settings_type_check CHECK (type IN ('int', 'string', 'bool'))
);

INSERT INTO settings (key, value, type, label, created_at, updated_at) VALUES
    ('checkin_tolerance_days', '3', 'int', 'Tolérance de pointage après échéance (jours)', NOW(), NOW()),
    ('late_payment_days', '5', 'int', 'Seuil de retard de paiement après échéance (jours)', NOW(), NOW()),
    ('inactivity_days', '14', 'int', 'Seuil d''absence sans pointage (jours)', NOW(), NOW()),
    ('expiry_soon_days', '3', 'int', 'Rappel d''échéance proche (jours avant)', NOW(), NOW()),
    ('receipt_prefix', 'RC', 'string', 'Préfixe des numéros de reçu', NOW(), NOW()),
    ('club_name', 'Phenix Club', 'string', 'Nom de la salle', NOW(), NOW()),
    ('club_phone', '', 'string', 'Téléphone de la salle', NOW(), NOW()),
    ('club_address', 'Brazzaville', 'string', 'Adresse de la salle', NOW(), NOW()),
    ('online_registration_enabled', '0', 'bool', 'Autoriser les demandes d''adhésion depuis le site web', NOW(), NOW()),
    ('registration_fee', '5000', 'int', 'Frais d''inscription (FCFA)', NOW(), NOW());

INSERT INTO expense_categories (name, sort_order, created_at, updated_at) VALUES
    ('Loyer', 1, NOW(), NOW()), ('Salaires', 2, NOW(), NOW()), ('Électricité', 3, NOW(), NOW()), ('Eau', 4, NOW(), NOW()),
    ('Matériel', 5, NOW(), NOW()), ('Entretien', 6, NOW(), NOW()), ('Communication', 7, NOW(), NOW()), ('Autre', 99, NOW(), NOW());

COMMIT;
