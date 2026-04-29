<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            // Extended fields for full rule management
            $table->string('target_device')->nullable()->after('description');   // device name or "all"
            $table->enum('metric_type', ['latency','status','cpu','memory','bandwidth','packet_loss'])
                  ->default('latency')->after('target_device');
            $table->enum('condition', ['gt','lt','eq','is_down','is_up'])
                  ->default('gt')->after('metric_type');
            $table->decimal('threshold_value', 10, 2)->nullable()->after('condition');
            $table->string('duration')->default('5m')->after('threshold_value');  // 1m,5m,10m,15m
            $table->integer('trigger_count')->default(0)->after('duration');
            $table->timestamp('last_triggered_at')->nullable()->after('trigger_count');
            $table->integer('sort_order')->default(0)->after('last_triggered_at');
        });
    }

    public function down(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            $table->dropColumn([
                'target_device','metric_type','condition','threshold_value',
                'duration','trigger_count','last_triggered_at','sort_order',
            ]);
        });
    }
};
