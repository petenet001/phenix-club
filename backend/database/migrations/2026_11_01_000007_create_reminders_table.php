<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('channel', 10);
            $table->text('message');
            $table->string('status', 10)->default('sent');
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'type', 'sent_at']);
        });

        DB::statement("ALTER TABLE reminders ADD CONSTRAINT reminders_type_check CHECK (type IN ('absence', 'late_payment', 'expiry_soon'))");
        DB::statement("ALTER TABLE reminders ADD CONSTRAINT reminders_channel_check CHECK (channel IN ('sms', 'whatsapp'))");
        DB::statement("ALTER TABLE reminders ADD CONSTRAINT reminders_status_check CHECK (status IN ('sent', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
