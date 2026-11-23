# Dokumentasi Teknis, Konfigurasi & Panduan Deploy — MBG NutriView

Dokumen ini merangkum arsitektur teknis, file konfigurasi terpusat, integrasi modul Machine Learning (Random Forest), prosedur pencadangan data, serta panduan penerapan (*deployment*) MBG NutriView.

---

## 1. Arsitektur & Tumpukan Teknologi

- **Framework Backend**: Laravel 12 (PHP 8.2+), arsitektur MVC dengan Service Layer terpisah:
  - `app/Services/KpiService.php` — Perhitungan 7 indikator KPI (`Sr, Sx, Sp, Sq, Sk, Sw, Sb`) & skor berbobot.
  - `app/Services/PredictionService.php` — Orkestrasi pemanggilan Python Random Forest dan mekanisme *fallback* aturan ambang batas (`FR-032`).
  - `app/Services/QrSvgService.php` — Pembangkitan QR Code SVG secara *offline* berbasis `chillerlan/php-qrcode`.
- **Basis Data**: SQLite (`database/database.sqlite` untuk pengembangan/uji cepat) atau MySQL / MariaDB (untuk produksi SPPG). Skema dikelola di `database/migrations/2026_11_12_000003_create_mbg_nutriview_tables.php`.
- **Antarmuka**: Blade Templates dengan desain tema *dark-navy* terpadu (`public/css/style.css`, Tailwind CSS utilities, logo resmi `public/images/logo-mbg.png`). Seluruh keluaran teks memakai escaping Blade `{{ ... }}` untuk mitigasi XSS.
- **Keamanan & Audit (`NFR-003`, `NFR-007`, `NFR-008`)**:
  - RBAC melalui middleware `CheckRole` (`app/Http/Middleware/CheckRole.php`).
  - *Rate limiting* login 5 kali percobaan / 60 detik di `AuthController` + pencatatan di tabel `audit_logs`.
  - Transaksi basis data `DB::transaction(...)` pada operasi tulis krusial di `AdminController`.
  - Penanganan pengecualian aman di `bootstrap/app.php` (`errors/generic.blade.php`) sehingga detail kueri/stack trace tidak tampil ketika `APP_DEBUG=false`.

---

## 2. Konfigurasi Parameter Terpusat (`config/mbg.php`)

Sesuai `NFR-006(b)` dan `OI-01`, seluruh bobot KPI, toleransi keterlambatan, batas tahap Petunjuk Teknis BGN, dan parameter ML disimpan di `config/mbg.php` (bisa ditimpa via `.env` tanpa mengubah kode PHP):

| Parameter | Kunci Konfigurasi | Default | Variabel `.env` Opsional |
|---|---|---|---|
| Bobot Indikator KPI (`Sr..Sb`) | `mbg.kpi.weights.*` | `0.10, 0.15, 0.10, 0.15, 0.25, 0.15, 0.10` | `MBG_WEIGHT_SR` .. `MBG_WEIGHT_SB` |
| Ambang Kategori Baik / Perbaikan | `mbg.kpi.thresholds.*` | `90.0` / `75.0` | `MBG_KPI_BAIK_MIN`, `MBG_KPI_PERBAIKAN_MIN` |
| Batas Konsumsi Aman | `mbg.operasional.batas_aman_menit` | `240` (4 jam) | `MBG_BATAS_AMAN_MENIT` |
| Ambang Waspada Risiko | `mbg.operasional.ambang_waspada_menit` | `200` menit | `MBG_AMBANG_WASPADA_MENIT` |
| Toleransi Terlambat (`T`) | `mbg.operasional.toleransi_terlambat_menit` | `15` menit | `MBG_TOLERANSI_TERLAMBAT_MENIT` |
| Target Selesai Tahap BGN | `mbg.operasional.batas_tahap.*` | `02:00`, `05:30`, `06:30`, `08:00` | *(ubah di `config/mbg.php`)* |
| Binary Python & Timeout | `mbg.ml.python_bin`, `timeout_seconds` | `'python'`, `5` detik | `MBG_PYTHON_BIN`, `MBG_PYTHON_TIMEOUT` |
| Retensi Cadangan DB | `mbg.backup.retention_days` | `14` hari | `MBG_BACKUP_RETENTION_DAYS` |

---

## 3. Modul Prediksi Random Forest (`scripts/`)

1. **`scripts/dataset_simulasi_mbg.csv`**: Dataset simulasi operasional berisi fitur `durasi_kemas_menit`, `estimasi_antar_menit`, `porsi`, `stok_kritis_flag`, `suhu_lingkungan_c`, dan label `Layak` / `Tidak Layak`.
2. **`scripts/train_evaluate_rf.py`**: Skrip pelatihan dan komparasi evaluasi model (`RandomForestClassifier` vs `DecisionTreeClassifier`, `LogisticRegression`, dan `RuleBased_200m`) menggunakan 5-fold Stratified Cross-Validation (Accuracy, Precision, Recall, F1). Menyimpan artefak model ke `scripts/rf_model.joblib` dan metrik ke `scripts/model_evaluation.json`.
   ```bash
   pip install scikit-learn joblib
   python scripts/train_evaluate_rf.py
   ```
3. **`scripts/random_forest_service.py`**: Dipanggil via `Symfony\Component\Process\Process` dari `PredictionService.php` dengan timeout 5 detik (`SI-01`). Jika `scripts/rf_model.joblib` ada dan `joblib` tersedia, prediksi dihitung dari probabilitas model Random Forest; jika belum terpasang atau Python gagal merespons dalam 5 detik, `PredictionService` otomatis mengaktifkan aturan ambang batas cadangan (`FR-032`) agar aplikasi tetap responsif.

---

## 4. Pencadangan Basis Data Otomatis (`OR-002`)

Perintah Artisan kustom tersedia untuk membuat cadangan berkala (mendukung SQLite dan MySQL/MariaDB via `mysqldump`):

```bash
php artisan mbg:backup
# Atau menentukan masa retensi khusus (hari):
php artisan mbg:backup --retention=30
```

Berkas cadangan disimpan pada `storage/app/backups/` (`mbg-backup-YYYYmmdd-His.sqlite` atau `.sql`), dan arsip yang melampaui retensi otomatis dibersihkan. Disarankan menjadwalkan perintah ini harian via Cron / Windows Task Scheduler.

---

## 5. Panduan Deployment Produksi

1. **Prasyarat Server**: PHP 8.2+ (`pdo_sqlite` / `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`), Composer, opsional Python 3.10+ (`scikit-learn`, `joblib`), dan web server (Nginx/Apache dengan HTTPS aktif).
2. **Konfigurasi `.env` Produksi**:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://mbg.sppg-ujunglare.id

   # Pilih MySQL atau SQLite sesuai infrastruktur SPPG
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mbg_nutriview
   DB_USERNAME=mbg_user
   DB_PASSWORD=<password_kuat>

   # Opsional jika Python berada di virtualenv khusus
   MBG_PYTHON_BIN=/opt/mbg-venv/bin/python
   ```
3. **Inisialisasi Awal & Optimasi**:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --seed
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. **Pengujian Pasca-Deploy**:
   - Pastikan `APP_DEBUG=false` menampilkan halaman ramah pada error 500 tanpa membocorkan stack trace.
   - Uji `php artisan mbg:backup` dan pastikan jadwal backup harian aktif.
   - Uji akses Wi-Fi LAN / HTTPS menggunakan `php artisan serve --host=0.0.0.0 --port=8000` (uji lokal) atau melalui VirtualHost Apache/Nginx (produksi).

