<?php

namespace App\Models;

use App\Enums\MemberStatus;
use App\Enums\RegistrationSource;
use App\Enums\SubscriptionStatus;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/** Authentifiable pour l'application mobile adhérent (token Sanctum, code d'accès app_password). */
class Member extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory, SoftDeletes;

    public function getAuthPasswordName(): string
    {
        return 'app_password';
    }

    /** Pas de "se souvenir de moi" : connexion par token uniquement. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public const CODE_PREFIX = 'PHX';

    protected $fillable = [
        'code',
        'last_name',
        'first_name',
        'gender',
        'birth_date',
        'phone',
        'email',
        'address',
        'photo_path',
        'emergency_contact_name',
        'emergency_contact_phone',
        'qr_token',
        'status',
        'registration_source',
        'validated_at',
        'validated_by',
        'joined_at',
        'notes',
        'app_password',
        'app_access_granted_at',
        'app_last_login_at',
    ];

    protected $hidden = [
        'app_password',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_at' => 'date',
            'status' => MemberStatus::class,
            'registration_source' => RegistrationSource::class,
            'validated_at' => 'datetime',
            'app_password' => 'hashed',
            'app_access_granted_at' => 'datetime',
            'app_last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            $member->code ??= static::generateCode();
            $member->qr_token ??= (string) Str::uuid();
            $member->joined_at ??= now()->toDateString();
            $member->status ??= MemberStatus::Pending;
            $member->registration_source ??= RegistrationSource::OnSite;
        });
    }

    /**
     * Génère le prochain code PHX-000001 (RG-ADH-01).
     * À appeler dans une transaction pour éviter les doublons sous forte concurrence.
     */
    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('code', 'like', static::CODE_PREFIX.'-%')
            ->orderByDesc('id')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen(static::CODE_PREFIX) + 1)) + 1 : 1;

        return sprintf('%s-%06d', static::CODE_PREFIX, $next);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->orderByDesc('end_date');
    }

    /**
     * Abonnement courant : actif en priorité, sinon le dernier non annulé (expiré ou à venir).
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', '<>', SubscriptionStatus::Cancelled->value)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->orderByDesc('end_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->orderByDesc('checked_in_at');
    }

    public function lastAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)->latestOfMany('checked_in_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->orderByDesc('sent_at');
    }

    public function trainingPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class)->orderByDesc('created_at');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    public function isSuspended(): bool
    {
        return $this->status === MemberStatus::Suspended;
    }

    /** Demande d'adhésion non encore validée (RG-ADH-05). */
    public function isPending(): bool
    {
        return $this->status === MemberStatus::Pending;
    }

    public function hasAppAccess(): bool
    {
        return $this->app_password !== null && ! $this->trashed();
    }

    /**
     * Les 9 derniers chiffres d'un numéro congolais : "+242 06 100 0001", "242061000001" et "061000001"
     * désignent le même téléphone.
     */
    public static function normalizePhone(string $phone): string
    {
        return substr(preg_replace('/\D/', '', $phone), -9);
    }

    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        $digits = static::normalizePhone($phone);

        return $query->whereRaw("RIGHT(regexp_replace(phone, '\\D', '', 'g'), 9) = ?", [$digits]);
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'ilike', "%{$term}%")
                ->orWhere('phone', 'ilike', "%{$term}%")
                ->orWhere(DB::raw("first_name || ' ' || last_name"), 'ilike', "%{$term}%")
                ->orWhere(DB::raw("last_name || ' ' || first_name"), 'ilike', "%{$term}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', MemberStatus::Pending->value);
    }
}
