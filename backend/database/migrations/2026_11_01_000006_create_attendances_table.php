<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->date('attended_on');
            $table->timestamp('checked_in_at');
            $table->string('method', 10)->default('manual');
            $table->boolean('forced')->default(false);
            $table->string('force_reason', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'attended_on']);
            $table->index('attended_on');
        });

        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_method_check CHECK (method IN ('manual', 'qr'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
