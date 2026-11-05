<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'checkin_tolerance_days', 'value' => '3', 'type' => 'int', 'label' => 'Tolérance de pointage après échéance (jours)'],
            ['key' => 'late_payment_days', 'value' => '5', 'type' => 'int', 'label' => 'Seuil de retard de paiement après échéance (jours)'],
            ['key' => 'inactivity_days', 'value' => '14', 'type' => 'int', 'label' => "Seuil d'absence sans pointage (jours)"],
            ['key' => 'expiry_soon_days', 'value' => '3', 'type' => 'int', 'label' => "Rappel d'échéance proche (jours avant)"],
            ['key' => 'receipt_prefix', 'value' => 'RC', 'type' => 'string', 'label' => 'Préfixe des numéros de reçu'],
            ['key' => 'club_name', 'value' => 'Phenix Club', 'type' => 'string', 'label' => 'Nom de la salle'],
            ['key' => 'club_phone', 'value' => '', 'type' => 'string', 'label' => 'Téléphone de la salle'],
            ['key' => 'club_address', 'value' => 'Brazzaville', 'type' => 'string', 'label' => 'Adresse de la salle'],
            ['key' => 'registration_fee', 'value' => '5000', 'type' => 'int', 'label' => 'Frais d\'inscription (FCFA)'],
            ['key' => 'online_registration_enabled', 'value' => '0', 'type' => 'bool', 'label' => 'Autoriser les demandes d\'adhésion depuis le site web'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
