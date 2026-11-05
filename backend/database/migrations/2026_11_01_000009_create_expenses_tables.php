<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('label', 150);
            $table->unsignedInteger('amount')->comment('Montant en FCFA');
            $table->string('method', 20);
            $table->string('payee', 150)->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('spent_on');
            $table->string('receipt_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index('spent_on');
        });

        DB::statement('ALTER TABLE expenses ADD CONSTRAINT expenses_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_method_check CHECK (method IN ('cash', 'mobile_money', 'bank_transfer'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
