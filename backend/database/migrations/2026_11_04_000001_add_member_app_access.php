<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Application mobile adhérent : identifiants de connexion et pointage depuis l'application
 * (scan du QR code tournant affiché sur la borne de la salle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('app_password')->nullable()->after('qr_token')->comment("Code d'accès à l'application (haché)");
            $table->timestamp('app_access_granted_at')->nullable()->after('app_password');
            $table->timestamp('app_last_login_at')->nullable()->after('app_access_granted_at');
        });

        DB::statement('ALTER TABLE attendances DROP CONSTRAINT attendances_method_check');
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_method_check CHECK (method IN ('manual', 'qr', 'app'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendances DROP CONSTRAINT attendances_method_check');
        DB::statement("UPDATE attendances SET method = 'qr' WHERE method = 'app'");
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_method_check CHECK (method IN ('manual', 'qr'))");

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['app_password', 'app_access_granted_at', 'app_last_login_at']);
        });
    }
};
