<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('price_at_subscription')->comment('Prix figé en FCFA');
            $table->unsignedInteger('sessions_used')->default(0);
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('end_date');
        });

        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_dates_check CHECK (end_date >= start_date)');
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status IN ('pending', 'active', 'expired', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
