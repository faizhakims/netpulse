<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_status')) {
            Schema::table('device_status', function (Blueprint $table) {
                $table->index(['device_id', 'checked_at']);
                $table->index('checked_at');
            });
        }

        if (Schema::hasTable('alert_history')) {
            Schema::table('alert_history', function (Blueprint $table) {
                $table->index(['sent_at', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('device_status')) {
            Schema::table('device_status', function (Blueprint $table) {
                $table->dropIndex(['device_id', 'checked_at']);
                $table->dropIndex(['checked_at']);
            });
        }

        if (Schema::hasTable('alert_history')) {
            Schema::table('alert_history', function (Blueprint $table) {
                $table->dropIndex(['sent_at', 'status']);
            });
        }
    }
};
