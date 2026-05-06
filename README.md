# NetPulse

NetPulse adalah dashboard monitoring jaringan berbasis web yang dibangun dengan Laravel 13. Aplikasi ini memantau perangkat jaringan (router, switch) secara real-time melalui SNMP dan SSH, menampilkan metrik latency, bandwidth, serta status perangkat, dan mengirimkan notifikasi otomatis ketika kondisi tertentu terpenuhi.

---

## Fitur Utama

- **Dashboard** — ringkasan status seluruh perangkat, health score, rata-rata latency, dan grafik tren latency 21 titik terbaru.
- **Devices** — daftar perangkat beserta status up/down/unknown secara real-time; mendukung tambah, hapus, ping, dan reboot perangkat.
- **Device Details** — metrik per perangkat: latency history, informasi SNMP, interface aktif.
- **Traffic** — pemantauan bandwidth interface per perangkat.
- **Incidents** — log insiden aktif dan terselesaikan beserta durasi downtime.
- **Alerts** — manajemen aturan threshold (latency, bandwidth, packet loss, status), cooldown per device, dan pengiriman notifikasi via Telegram atau email.
- **Logs** — riwayat pengiriman notifikasi alert.
- **Settings** — konfigurasi umum, monitoring interval, keamanan, profil pengguna, manajemen user, dan backup manual.

---

## Persyaratan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.3 |
| Laravel | 13.x |
| Database | MySQL / MariaDB |
| Node.js | (untuk build aset frontend) |
| Composer | 2.x |

---

## Instalasi

### 1. Clone repositori

```bash
git clone <url-repositori> netpulse
cd netpulse
```

### 2. Setup otomatis

Script `setup` di `composer.json` menjalankan semua langkah sekaligus:

```bash
composer run setup
```

Perintah ini melakukan:
- `composer install`
- Salin `.env.example` ke `.env` (jika belum ada)
- Generate application key
- Jalankan seluruh migrasi database
- `npm install` dan `npm run build`

### 3. Seed data awal (opsional)

```bash
php artisan db:seed
```

Ini akan membuat user default dan data contoh insiden/alert.

---

## Menjalankan Aplikasi

Gunakan perintah berikut untuk menjalankan semua proses sekaligus (server, queue worker, log viewer, dan Vite):

```bash
composer run dev
```

Atau jalankan secara terpisah:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

Aplikasi dapat diakses di `http://localhost:8000`.

---

## Artisan Commands

### Evaluasi Alert Rules

```bash
php artisan alerts:check
```

Mengevaluasi semua rule aktif terhadap data terbaru setiap perangkat. Jika kondisi threshold terpenuhi, notifikasi dikirim dan insiden dibuat secara otomatis.

Gunakan flag `--debug` untuk melihat detail evaluasi setiap rule:

```bash
php artisan alerts:check --debug
```

### Resolve Stale Incidents

```bash
php artisan incidents:resolve-stale
```

Menutup insiden yang sudah tidak aktif berdasarkan status terkini perangkat.

Kedua command ini sebaiknya dijadwalkan melalui cron:

```
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

---

## Struktur Direktori

```
app/
  Console/Commands/     # CheckAlertRules, ResolveStaleIncidents
  Http/Controllers/     # AuthController, DashboardController, DeviceController,
                        # TrafficController, IncidentController, AlertController,
                        # LogsController, SettingsController
  Models/               # Device, DeviceStatus, Incident, AlertRule,
                        # AlertHistory, AlertChannel, InterfaceTraffic,
                        # SnmpMetric, SystemSetting, User
config/
  netpulse.php          # Konfigurasi spesifik aplikasi (main_router)
database/
  migrations/           # Skema tabel: incidents, alert_rules, alert_channels,
                        # alert_history, settings, device_status
  seeders/              # UserSeeder, IncidentAlertSeeder
resources/views/        # Blade templates: dashboard, device, details, traffic,
                        # incidents, alert, logs, settings, login
routes/
  web.php               # Seluruh route (auth + protected)
public/
  css/                  # Stylesheet per halaman
  js/                   # device-actions.js, finisher-header
```

---

## Alert System

Alert rule mendukung empat tipe metrik:

| Metrik | Satuan | Kondisi |
|---|---|---|
| `latency` | ms | `gt`, `lt`, `eq` |
| `bandwidth` | Mbps | `gt`, `lt`, `eq` |
| `packet_loss` | % | `gt`, `lt`, `eq` |
| `status` | — | `is_down`, `is_up` |

Setiap rule memiliki:
- **Target device** — perangkat tertentu atau semua perangkat.
- **Duration** — durasi kondisi harus terpenuhi sebelum trigger (contoh: `5m`).
- **Severity** — `critical`, `warning`, atau `info`.
- **Channels** — Telegram dan/atau email (konfigurasi di halaman Settings > Alerts).
- **Cooldown** — mencegah spam notifikasi untuk perangkat yang sama.

---

## Lisensi

Proyek ini dirilis di bawah lisensi [MIT](https://opensource.org/licenses/MIT).
