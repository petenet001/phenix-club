<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('price')->comment('Prix en FCFA');
            $table->unsignedInteger('sessions_count')->nullable()->comment('Null = illimité');
            $table->unsignedInteger('registration_fee')->nullable()->comment('Frais d\'inscription spécifiques, null = paramètre global');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE subscription_plans ADD CONSTRAINT subscription_plans_duration_check CHECK (duration_days > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
