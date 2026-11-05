<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('PHX-000001');
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->char('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 20)->unique();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->uuid('qr_token')->unique();
            $table->string('status', 20)->default('pending');
            $table->string('registration_source', 10)->default('on_site');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('joined_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('status');
        });

        DB::statement("ALTER TABLE members ADD CONSTRAINT members_gender_check CHECK (gender IS NULL OR gender IN ('M', 'F'))");
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_status_check CHECK (status IN ('pending', 'active', 'inactive', 'suspended'))");
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_registration_source_check CHECK (registration_source IN ('on_site', 'online'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
