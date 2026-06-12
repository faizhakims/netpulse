<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hapus 'cpu' dan 'memory' dari enum metric_type di alert_rules.
     * MariaDB tidak support ALTER COLUMN untuk enum secara langsung,
     * jadi kita pakai MODIFY COLUMN.
     *
     * SQLite (used in testing) does not support MODIFY COLUMN / ENUM.
     * We skip the DDL statement on SQLite since the column is already a string.
     */
    public function up(): void
    {
        // Remove any stale rules with metric cpu/memory (not supported by monitoring engine)
        DB::table('alert_rules')
            ->whereIn('metric_type', ['cpu', 'memory'])
            ->delete();

        // Only run the MySQL/MariaDB-specific ENUM narrowing on real MySQL connections
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE alert_rules
                MODIFY COLUMN metric_type
                ENUM('latency','status','bandwidth','packet_loss')
                NOT NULL DEFAULT 'latency'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE alert_rules
                MODIFY COLUMN metric_type
                ENUM('latency','status','cpu','memory','bandwidth','packet_loss')
                NOT NULL DEFAULT 'latency'
            ");
        }
    }
};
