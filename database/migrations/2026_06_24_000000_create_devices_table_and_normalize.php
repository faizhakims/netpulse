<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('ip_address')->nullable();
                $table->string('layer')->nullable();
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        if ($driver !== 'sqlite') {
            if (Schema::hasTable('devices')) {
                DB::statement('ALTER TABLE devices ENGINE = InnoDB');
                DB::statement('ALTER TABLE devices MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');
            }
            if (Schema::hasTable('device_status')) {
                DB::statement('ALTER TABLE device_status ENGINE = InnoDB');
            }
            if (Schema::hasTable('interface_traffic')) {
                DB::statement('ALTER TABLE interface_traffic ENGINE = InnoDB');
            }
            if (Schema::hasTable('snmp_metrics')) {
                DB::statement('ALTER TABLE snmp_metrics ENGINE = InnoDB');
            }
            if (Schema::hasTable('incidents')) {
                DB::statement('ALTER TABLE incidents ENGINE = InnoDB');
            }
            if (Schema::hasTable('alert_rules')) {
                DB::statement('ALTER TABLE alert_rules ENGINE = InnoDB');
            }
        }

        if (Schema::hasTable('device_status')) {
            if ($driver !== 'sqlite') {
                DB::statement('INSERT IGNORE INTO devices (name, ip_address, created_at, updated_at) SELECT device, MAX(ip_address), NOW(), NOW() FROM device_status GROUP BY device');
            }

            if (!Schema::hasColumn('device_status', 'device_id')) {
                Schema::table('device_status', function (Blueprint $table) {
                    $table->foreignId('device_id')->nullable()->after('id')->constrained('devices')->cascadeOnDelete();
                });
            }

            if ($driver !== 'sqlite') {
                DB::statement('UPDATE device_status ds JOIN devices d ON ds.device = d.name SET ds.device_id = d.id');

                DB::unprepared('
                    CREATE TRIGGER before_insert_device_status
                    BEFORE INSERT ON device_status
                    FOR EACH ROW
                    BEGIN
                        DECLARE v_id INT;
                        SELECT id INTO v_id FROM devices WHERE name = NEW.device LIMIT 1;
                        IF v_id IS NULL THEN
                            INSERT INTO devices (name, ip_address, created_at, updated_at) VALUES (NEW.device, NEW.ip_address, NOW(), NOW());
                            SET v_id = LAST_INSERT_ID();
                        END IF;
                        SET NEW.device_id = v_id;
                    END
                ');
            }
        }

        if (Schema::hasTable('interface_traffic')) {
            if ($driver !== 'sqlite') {
                DB::statement('INSERT IGNORE INTO devices (name, ip_address, created_at, updated_at) SELECT device, MAX(ip_address), NOW(), NOW() FROM interface_traffic GROUP BY device');
            }

            if (!Schema::hasColumn('interface_traffic', 'device_id')) {
                Schema::table('interface_traffic', function (Blueprint $table) {
                    $table->foreignId('device_id')->nullable()->after('id')->constrained('devices')->cascadeOnDelete();
                });
            }

            if ($driver !== 'sqlite') {
                DB::statement('UPDATE interface_traffic it JOIN devices d ON it.device = d.name SET it.device_id = d.id');

                DB::unprepared('
                    CREATE TRIGGER before_insert_interface_traffic
                    BEFORE INSERT ON interface_traffic
                    FOR EACH ROW
                    BEGIN
                        DECLARE v_id INT;
                        SELECT id INTO v_id FROM devices WHERE name = NEW.device LIMIT 1;
                        IF v_id IS NULL THEN
                            INSERT INTO devices (name, ip_address, created_at, updated_at) VALUES (NEW.device, NEW.ip_address, NOW(), NOW());
                            SET v_id = LAST_INSERT_ID();
                        END IF;
                        SET NEW.device_id = v_id;
                    END
                ');
            }
        }

        if (Schema::hasTable('snmp_metrics')) {
            if ($driver !== 'sqlite') {
                DB::statement('INSERT IGNORE INTO devices (name, created_at, updated_at) SELECT device, NOW(), NOW() FROM snmp_metrics GROUP BY device');
            }

            if (!Schema::hasColumn('snmp_metrics', 'device_id')) {
                Schema::table('snmp_metrics', function (Blueprint $table) {
                    $table->foreignId('device_id')->nullable()->after('id')->constrained('devices')->cascadeOnDelete();
                });
            }

            if ($driver !== 'sqlite') {
                DB::statement('UPDATE snmp_metrics sm JOIN devices d ON sm.device = d.name SET sm.device_id = d.id');

                DB::unprepared('
                    CREATE TRIGGER before_insert_snmp_metrics
                    BEFORE INSERT ON snmp_metrics
                    FOR EACH ROW
                    BEGIN
                        DECLARE v_id INT;
                        SELECT id INTO v_id FROM devices WHERE name = NEW.device LIMIT 1;
                        IF v_id IS NULL THEN
                            INSERT INTO devices (name, created_at, updated_at) VALUES (NEW.device, NOW(), NOW());
                            SET v_id = LAST_INSERT_ID();
                        END IF;
                        SET NEW.device_id = v_id;
                    END
                ');
            }
        }

        if (Schema::hasTable('incidents')) {
            if (!Schema::hasColumn('incidents', 'device_id')) {
                Schema::table('incidents', function (Blueprint $table) {
                    $table->foreignId('device_id')->nullable()->after('id')->constrained('devices')->cascadeOnDelete();
                });
            }

            if ($driver !== 'sqlite') {
                DB::statement('UPDATE incidents i JOIN devices d ON i.device = d.name SET i.device_id = d.id');
            }

            if (Schema::hasColumn('incidents', 'device')) {
                Schema::table('incidents', function (Blueprint $table) {
                    $table->dropColumn(['device', 'ip_address']);
                });
            }
        }

        if (Schema::hasTable('alert_rules')) {
            if (!Schema::hasColumn('alert_rules', 'target_device_id')) {
                Schema::table('alert_rules', function (Blueprint $table) {
                    $table->foreignId('target_device_id')->nullable()->after('target_device')->constrained('devices')->cascadeOnDelete();
                });
            }

            if ($driver !== 'sqlite') {
                DB::statement('UPDATE alert_rules a JOIN devices d ON a.target_device = d.name SET a.target_device_id = d.id WHERE a.target_device IS NOT NULL AND a.target_device != "all"');
            }

            if (Schema::hasColumn('alert_rules', 'target_device')) {
                Schema::table('alert_rules', function (Blueprint $table) {
                    $table->dropColumn('target_device');
                });
            }
        }
    }

    public function down(): void
    {
    }
};
