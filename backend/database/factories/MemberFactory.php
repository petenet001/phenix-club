<?php

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Enums\RegistrationSource;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Member> */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'last_name' => fake('fr_FR')->lastName(),
            'first_name' => fake('fr_FR')->firstName(),
            'gender' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'phone' => '+24206'.fake()->unique()->numerify('#######'),
            'address' => fake()->randomElement(['Moungali', 'Poto-Poto', 'Bacongo', 'Talangaï', 'Ouenzé', 'Mfilou']),
            'qr_token' => (string) Str::uuid(),
            'status' => MemberStatus::Inactive,
            'registration_source' => RegistrationSource::OnSite,
            'validated_at' => now(),
            'joined_at' => now()->toDateString(),
        ];
    }

    /** Demande d'adhésion non validée. */
    public function pending(): static
    {
        return $this->state(['status' => MemberStatus::Pending, 'validated_at' => null]);
    }

    public function online(): static
    {
        return $this->state(['registration_source' => RegistrationSource::Online]);
    }

    public function active(): static
    {
        return $this->state(['status' => MemberStatus::Active]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => MemberStatus::Suspended]);
    }
}
