<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Alert channel config (Telegram & SMTP) ──────────────────────────
        Schema::create('alert_channels', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['telegram', 'email']);
            $table->boolean('is_active')->default(true);
            $table->json('config');          // { token, chat_id } or { host, port, user, pass }
            $table->timestamps();
        });

        // ── Threshold rules ──────────────────────────────────────────────────
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning');
            $table->string('title');
            $table->string('description');
            $table->json('channels');        // ["telegram","email"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Alert delivery history ───────────────────────────────────────────
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->nullable()->constrained('alert_rules')->nullOnDelete();
            $table->enum('channel', ['telegram', 'email']);
            $table->string('recipient');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('message');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('alert_channels');
    }
};
