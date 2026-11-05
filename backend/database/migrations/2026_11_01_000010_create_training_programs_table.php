<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('kind', 20)->comment('training | nutrition');
            $table->string('title', 150);
            $table->string('status', 20)->default('proposed');
            $table->boolean('proposed_by_member')->default(false);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->jsonb('content')->default('{}');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
        });

        DB::statement("ALTER TABLE training_programs ADD CONSTRAINT training_programs_kind_check CHECK (kind IN ('training', 'nutrition'))");
        DB::statement("ALTER TABLE training_programs ADD CONSTRAINT training_programs_status_check CHECK (status IN ('proposed', 'approved', 'declined', 'imposed', 'archived'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
