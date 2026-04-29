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
     * Sebelum menjalankan migration ini, pastikan tidak ada rule aktif
     * dengan metric_type = 'cpu' atau 'memory' (tidak akan pernah trigger).
     */
    public function up(): void
    {
        // Hapus rule lama dengan metric cpu/memory (tidak ada data sumbernya)
        DB::table('alert_rules')
            ->whereIn('metric_type', ['cpu', 'memory'])
            ->delete();

        // Update enum kolom metric_type
        DB::statement("
            ALTER TABLE alert_rules
            MODIFY COLUMN metric_type
            ENUM('latency','status','bandwidth','packet_loss')
            NOT NULL DEFAULT 'latency'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE alert_rules
            MODIFY COLUMN metric_type
            ENUM('latency','status','cpu','memory','bandwidth','packet_loss')
            NOT NULL DEFAULT 'latency'
        ");
    }
};
