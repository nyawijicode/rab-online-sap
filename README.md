# RAB Online — Laravel ^12.0 + Filament ^3.3

RAB Online adalah aplikasi internal berbasis **Laravel** & **Filament** untuk mengelola pengajuan RAB (Asset, Dinas, Kegiatan, Promosi, Kebutuhan, Biaya Service), modul **Service**, serta **Request Teknisi** lengkap dengan PDF/XLSX export, role & permission, dan integrasi komponen pendukung.

> Repo ini telah disesuaikan untuk **PHP ^8.2**, **Laravel ^12.0**, **Filament ^3.3**, **Maatwebsite/Excel ^3.1**, **spatie/laravel-permission ^6.21**, dan **barryvdh/laravel-dompdf ^3.1**.

---

## ✨ Fitur Utama

- **Pengajuan RAB**: tipe-tipe pengajuan meliputi Asset, Dinas (+aktivitas & personil), Kegiatan, Promosi, Kebutuhan, dan **Biaya Service**.
- **Lampiran**: dukungan lampiran per tipe (asset/dinas/kebutuhan/kegiatan/promosi), termasuk varian Pusat & Cabang untuk Marcomm Kegiatan.
- **Service**: manajemen service dengan **Staging** (lihat `App\Enums\StagingEnum`) dan perubahan status otomatis via Observer.
- **Request Teknisi**: pencatatan pekerjaan teknisi, laporan, serta **export XLSX** (all/filtered).
- **PDF Export**: cetak/preview/download PDF untuk Pengajuan (berbagai template) & Pengajuan Biaya Service.
- **XLSX Export**: ekspor data **Pengajuan**, **Service**, dan **Request Teknisi** (All & Filtered) via controller `Export*Controller` & kelas `*Export`.
- **Role & Permission**: berbasis **spatie/laravel-permission** (lihat `database/seeders/RoleSeeder.php`). 
- **Data Referensi**: Cabang, Divisi, Tipe RAB, Status User, dll (tersedia seeder).

---

## 🧱 Arsitektur & Struktur Penting

```
app/
  Enums/
    StagingEnum.php
  Exports/
    PengajuansExport.php
    RequestTeknisiAllExport.php
    RequestTeknisiFilteredExport.php
    ServicesAllExport.php
    ServicesFilteredExport.php
  Filament/
    Resources/ (... banyak Resource untuk Pengajuan, Service, RequestTeknisi, Master Data, dll)
  Http/Controllers/
    CetakPengajuanServiceController.php
    ExportPengajuansController.php
    ExportPenggunaanMobilController.php
    ExportPenggunaanTeknisiController.php
    ExportRequestTeknisiController.php
    ExportServiceController.php
  Models/
    (Cabang, Divisi, Pengajuan*, Lampiran*, Persetujuan*, RequestTeknisi*, Service, ProjectMonitor, CustomerMonitor, ...)
  Observers/
    PengajuanObserver.php
    ServiceObserver.php
  Services/
    ServiceLogService.php
    StagingLogService.php

config/
  filament.php, excel.php, queue.php, permission.php, ...

database/
  migrations/ (≥ 50 migrasi untuk semua modul)
  seeders/
    CabangSeeder.php, DatabaseSeeder.php, DivisiSeeder.php, PersetujuanSeeder.php, RoleSeeder.php, TipeRABSeeder.php, UserSeeder.php, UserStatusSeeder.php

routes/
  web.php  (route PDF & export + redirect ke /web untuk Filament)
```

---

## 🛠️ Prasyarat

- **PHP ^8.2** + ekstensi umum (OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo)
- **Composer** v2.x
- **Node.js** LTS + **npm**
- **MySQL/MariaDB** (atau DB lain yang didukung Laravel)
- (Opsional) **Redis** untuk queue/cache

---

## ⚙️ Instalasi & Setup Lokal

1. **Clone** repo dan masuk ke direktori proyek.
2. **Composer install**
   ```bash
   composer install
   ```
3. **Salin .env & generate key**
   ```bash
   cp .env.example .env   # jika belum ada, buat manual dari contoh di bawah
   php artisan key:generate
   ```
4. **Konfigurasi .env** (contoh minimal):
   ```env
   APP_NAME="RAB Online"
   APP_ENV=local
   APP_KEY=base64:...           # akan terisi otomatis setelah key:generate
   APP_DEBUG=true
   APP_URL=http://localhost

   LOG_CHANNEL=stack
   LOG_LEVEL=debug

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rab_online
   DB_USERNAME=root
   DB_PASSWORD=

   # Filesystem
   FILESYSTEM_DISK=public

   # Queue (disarankan untuk eksport besar)
   QUEUE_CONNECTION=database
   ```
5. **Migrate & Seed**
   ```bash
   php artisan migrate --seed
   ```
   Seeder yang dipakai: **CabangSeeder.php, DatabaseSeeder.php, DivisiSeeder.php, PersetujuanSeeder.php, RoleSeeder.php, TipeRABSeeder.php, UserSeeder.php, UserStatusSeeder.php**.  
   Cek `RoleSeeder`/`UserSeeder` untuk kredensial login awal & daftar role.
6. **Install asset frontend (Vite + Tailwind v4)**
   ```bash
   npm install
   npm run dev   # atau npm run build untuk produksi
   ```
7. **Jalankan server lokal**
   ```bash
   php artisan serve
   ```
   Akses **Filament** via `http://localhost/web` (route root `/` redirect ke `/web`).

---

## 🔐 Autentikasi, Role, & Permission

- **spatie/laravel-permission** digunakan untuk role/permission.
- Role default & mapping permission diatur melalui **seeder** (`database/seeders/RoleSeeder.php`).
- Gunakan Filament untuk mengelola user, role, dan permission (Resource terkait tersedia di `app/Filament/Resources`).

---

## 🧩 Modul & Alur Utama

### 1) Pengajuan RAB
- **Tipe**: Asset, Dinas (dengan `pengajuan_dinas_activities` & `pengajuan_dinas_personils`), Kegiatan, Promosi, Kebutuhan, **Biaya Service**.
- **Lampiran**: tabel lampiran spesifik setiap tipe (mis. `lampiran_marcomm_*`, `lampiran_*`).
- **Status & Persetujuan**: melalui `persetujuans`, `persetujuan_approvers`, dan `pengajuan_statuses`.
- **PDF**: route `GET /pengajuan/{id}/pdf` (preview) & `GET /pengajuan/{id}/download-pdf?mode=user|internal`.

### 2) Service
- **Staging**: enum `StagingEnum` dan **ServiceObserver** untuk logging/perubahan state.
- **Export**: XLSX All/Filtered melalui `ServicesAllExport` / `ServicesFilteredExport` + controller.

### 3) Request Teknisi
- **Data Utama**: `request_teknisis` & `request_teknisi_reports`.
- **Export**: `GET /exports/request-teknisi/all` & `.../filtered` (lihat `ExportRequestTeknisiController`).

> Catatan: detail path/parameter filter dapat dilihat langsung pada masing-masing Controller `Export*Controller` & kelas `*FilteredExport`.

---

## 🧾 PDF & XLSX

- **PDF** via `barryvdh/laravel-dompdf`:
  - Template view berada di `resources/views/pdf/*`.
  - Contoh route PDF untuk Pengajuan & Biaya Service ada di `routes/web.php`.
- **XLSX** via `maatwebsite/excel`:
  - Kelas export berada di `app/Exports/*`.
  - Controller export: `app/Http/Controllers/Export*Controller.php`.

---

## 🚚 Deployment (Ringkas)

1. **Build asset**: `npm run build`
2. **Upload/Deploy kode** ke server (FTP/CI-CD).
3. **Composer install (no-dev)**: `composer install --no-dev --optimize-autoloader`
4. **Konfigurasi .env** produksi (APP_ENV, APP_DEBUG=false, APP_URL, DB, QUEUE, dsb.)
5. **Migrasi**: `php artisan migrate --force`
6. **Optimisasi**:
   ```bash
   php artisan optimize:clear
   php artisan filament:optimize
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
7. (Opsional) **Queue worker** untuk export besar: Supervisor/cron atau `php artisan queue:work`

> Untuk setup CI/CD via GitHub Actions + FTP cPanel, siapkan **secrets** FTP dan jalankan job yang: (a) mengunduh repo, (b) menyinkronkan file (kecuali `storage`, `.env`, dll), (c) menjalankan perintah artisan di server (bisa via cron/HTTP hook).

---

## 🧪 Testing

- **PHPUnit** tersedia (`phpunit.xml`), jalankan:
  ```bash
  php artisan test
  ```
- Tambahkan test untuk Resource/Export/Observer sesuai kebutuhan.

---

## 🗺️ Rute Penting (ringkasan)

- Redirect root ke Filament: `GET /` → `/web`
- **Pengajuan PDF**: 
  - `GET /pengajuan/{pengajuan}/pdf` (preview)
  - `GET /pengajuan/{pengajuan}/download-pdf?mode=user|internal`
- **Pengajuan Biaya Service PDF**:
  - `GET /pengajuan-biaya-service/{pengajuan}/download-pdf?tipe=internal|pelanggan`
- **Export** (contoh):
  - `GET /exports/request-teknisi/all`
  - `GET /exports/request-teknisi/filtered`
  - `GET /exports/pengajuan-biaya-service/all`
  - (Cek controller `Export*` untuk varian lainnya & parameter filter)

---

## 📦 Versi & Dependensi Utama

- **Laravel**: ^12.0
- **Filament**: ^3.3
- **PHP**: ^8.2
- **Tailwind CSS**: 4.x + Vite
- **Excel**: ^3.1
- **Permission**: ^6.21
- **DOMPDF**: ^3.1

---

## 📚 Catatan Developer

- Gunakan **Observer** & **Service** internal (`ServiceLogService`, `StagingLogService`) untuk konsistensi log & state.
- Semua tipe pengajuan memiliki struktur data & lampiran spesifik — cek **migrations** dan **Resources** Filament untuk field yang benar sebelum seed/tes.
- Bila export **filtered** tidak sesuai hasil filter, cek logika query di kelas `*FilteredExport` dan controller terkait.
- Perubahan role/permission sebaiknya dilakukan via **Seeder** agar konsisten antar environment.

---

## 🧰 Troubleshooting Singkat

- **PDF tidak keluar / blank** → pastikan view ada & data lengkap; coba set `APP_DEBUG=true` lokal.
- **Export kosong** → cek filter request & query di `*FilteredExport`.
- **Filament tidak tampil** → pastikan route ke `/web`, user memiliki role/permission yang benar.
- **Upload/Storage** → set `FILESYSTEM_DISK=public`, jalankan `php artisan storage:link`.

---

## 👥 Lisensi & Kredit

Aplikasi internal **RAB Online** — dikembangkan dengan Laravel & Filament.
Dependensi open-source mengikuti lisensi masing-masing paket.

---

_Diperbarui: 2025-11-06 10:50 _
