<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->unique()->comment('RC-2026-000001');
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('type', 20)->default('subscription');
            $table->unsignedInteger('amount')->comment('Montant en FCFA');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->timestamp('paid_at');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index(['member_id', 'paid_at']);
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_type_check CHECK (type IN ('registration', 'subscription', 'other'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('cash', 'mobile_money', 'bank_transfer'))");
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_period_check CHECK (period_end IS NULL OR period_start IS NULL OR period_end >= period_start)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
