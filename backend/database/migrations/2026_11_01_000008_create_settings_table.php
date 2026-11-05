<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->text('value')->nullable();
            $table->string('type', 10)->default('string');
            $table->string('label', 100);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE settings ADD CONSTRAINT settings_type_check CHECK (type IN ('int', 'string', 'bool'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
