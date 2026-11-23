# Laporan Pengujian STQA & Requirements Traceability Matrix (RTM) — MBG NutriView

Dokumen ini mencatat hasil verifikasi perangkat lunak **MBG NutriView** terhadap spesifikasi **SRS** (`FR-001`–`FR-032`, `NFR-001`–`NFR-008`, `OR-001`–`OR-004`, `TC-001`–`TC-036`) dan **PRD** (`AC-01`–`AC-08`).

---

## 1. Ringkasan Eksekusi Pengujian Otomatis

- **Suite Pengujian**: `tests/Feature/MbgNutriViewTest.php` + `tests/Feature/ExampleTest.php` + `tests/Unit/ExampleTest.php`
- **Perintah Eksekusi**: `F:\XAMPP\php\php.exe artisan test`
- **Hasil Terakhir**: **24 passed (140 assertions)**, **0 failed**, **0 skipped**, durasi **6.61s**.
- **Pengujian CLI Cadangan (`OR-002`)**: `F:\XAMPP\php\php.exe artisan mbg:backup` berhasil menghasilkan arsip `storage/app/backups/mbg-backup-20261113-165220.sqlite`.
- **Pemeriksaan Keamanan Template**: Pemindaian `{!! ... !!}` pada `resources/views/**/*.blade.php` mengembalikan hasil kosong (seluruh keluaran memakai Blade escaping).

---

## 2. Requirements Traceability Matrix (RTM — Test Case SRS)

| ID TC | Kebutuhan Terkait | Deskripsi Uji | Metode Verifikasi (`MbgNutriViewTest`) | Status |
|---|---|---|---|---|
| `TC-001` | `FR-001` | Login berhasil Admin & Ahli Gizi sesuai peran | `test_login_berhasil_admin_dan_gizi` | Lulus |
| `TC-002` | `FR-002`, `NFR-003(d)` | Penolakan login salah kredensial + pesan generik + throttling 5x (`audit_logs`) | `test_login_gagal_kredensial_salah`, `test_login_throttling_mencatat_audit_log_setelah_lima_gagal` | Lulus |
| `TC-003` | `FR-003`, `FR-004` | Pembatasan RBAC antar-peran & redirect halaman tanpa sesi | `test_rbac_pembatasan_kewenangan_peran`, `test_unauthenticated_diarahkan_ke_login` | Lulus |
| `TC-004`, `TC-005` | `FR-005` | Tambah, ubah & tampilkan menu dan bahan baku | `test_admin_crud_dinamis_dan_pencatatan_tahap_memicu_kpi`, `test_render_semua_tampilan_sesuai_desain_figma` | Lulus |
| `TC-006` | `FR-006` | Penolakan input stok bahan <= 0 atau tanggal kedaluwarsa <= masuk | `test_validasi_bahan_baku_ditolak_jika_stok_nol_atau_kedaluwarsa_tidak_valid` | Lulus |
| `TC-007` | `FR-007` | Pengubahan bahan pada menu terpublikasi me-rollback status ke `menunggu_validasi` | `test_menu_rollback_menunggu_validasi_saat_bahan_diubah` | Lulus |
| `TC-008`, `TC-009` | `FR-008`, `FR-009` | Perhitungan gizi per sajian dari master gizi 100g | `test_perhitungan_gizi_menu` | Lulus |
| `TC-010`, `TC-011` | `FR-010`, `FR-011` | Blokir validasi bila gizi bahan belum lengkap & revisi wajib `catatan_revisi` | `test_ahli_gizi_gagal_validasi_jika_gizi_tidak_lengkap_dan_revisi_wajib_catatan` | Lulus |
| `TC-012`–`TC-014` | `FR-012`–`FR-014` | Publikasi menu tervalidasi ke portal publik & tarik publikasi | `test_aturan_publikasi_menu_ahli_gizi`, `test_kpi_service_presisi_batas_empat_jam_dan_tarik_publikasi` | Lulus |
| `TC-015`, `TC-017` | `FR-015`, `FR-017` | Akses menu terpublikasi publik & penolakan token QR tidak valid / belum terpublikasi | `test_portal_publik_dan_penanganan_qr`, `test_portal_publik_dinamis_dan_validasi_qr_ketat` | Lulus |
| `TC-016` | `FR-016` | Akses read-only Ahli Gizi atas menu, bahan & KPI tanpa mutasi | `test_ahli_gizi_akses_lihat_menu_dan_kpi_readonly` | Lulus |
| `TC-018` | `FR-018`, `FR-025` | Pencatatan tahap aktual memicu pembaruan KPI | `test_admin_crud_dinamis_dan_pencatatan_tahap_memicu_kpi` | Lulus |
| `TC-019` | `FR-019` | Penolakan tahap dengan waktu mulai/selesai/petugas kosong atau selesai < mulai | `test_pencatatan_tahap_menolak_waktu_tidak_lengkap_dan_peringatan_urutan` | Lulus |
| `TC-020` | `FR-020` | Peringatan urutan tahap apabila tahap sebelumnya belum tercatat | `test_pencatatan_tahap_menolak_waktu_tidak_lengkap_dan_peringatan_urutan` | Lulus |
| `TC-021`, `TC-022` | `FR-021`, `FR-022` | Pengelolaan kelompok penerima, jadwal produksi & deteksi jadwal bentrok | `test_deteksi_jadwal_bentrok`, `test_admin_crud_dinamis_dan_pencatatan_tahap_memicu_kpi` | Lulus |
| `TC-023`, `TC-024` | `FR-023`, `FR-024` | Tampilan publik alur operasional & jadwal terencana tanpa bocor akses edit | `test_alur_publik_menampilkan_jadwal_produksi_dan_status_terlambat`, `test_render_semua_tampilan_sesuai_desain_figma` | Lulus |
| `TC-026` | `FR-026` | Penandaan otomatis `status_tahap = terlambat` bila melewati target jadwal + toleransi `T` | `test_alur_publik_menampilkan_jadwal_produksi_dan_status_terlambat` | Lulus |
| `TC-027`–`TC-036` | `FR-027`–`FR-032` | Penolakan KPI bila tahap tidak lengkap, batas 4 jam (`240m`), fallback `FR-032`, peringatan kedaluwarsa, KPI per titik, & audit log | `test_kpi_service_presisi_batas_empat_jam_dan_tarik_publikasi`, `test_prediksi_kelayakan_batas_kritis_kpi_per_titik_dan_audit_log`, `test_prediksi_risiko_dan_fallback` | Lulus |

---

## 3. Verifikasi Kebutuhan Non-Fungsional & Operasional

| Kode | Spesifikasi | Realisasi Implementasi | Status |
|---|---|---|---|
| `NFR-001`–`NFR-002` | Respons UI < 3s; kueri terindeks & eager loading | Timeout proses Python 5s; eager loading `with()` diterapkan di controller; seluruh fitur uji tuntas dalam ~6.6s | Terpenuhi |
| `NFR-003` | Bcrypt hash, CSRF, rate limit 5x/menit, RBAC middleware | `Hash::check`, `@csrf`, `RateLimiter` di `AuthController`, `CheckRole` middleware + `AuditLog` | Terpenuhi |
| `NFR-005` | Tampilan konsisten tema *dark-navy* Figma & responsive | CSS terpusat `public/css/style.css`, logo resmi `logo-mbg.png`, diuji pada 10 tampilan utama | Terpenuhi |
| `NFR-006(b)` | Parameterisasi bobot KPI, toleransi `T`, ambang tanpa ubah kode | Dikonfigurasi di `config/mbg.php` + `.env`; diuji di `test_keamanan_xss_sqli_config_kpi_error_ramah_dan_backup` | Terpenuhi |
| `NFR-006(c)` | Dokumentasi pengguna, teknis/deploy, dan laporan STQA | `docs/PANDUAN_PENGGUNA.md`, `docs/DOKUMENTASI_TEKNIS_DAN_DEPLOY.md`, `docs/LAPORAN_PENGUJIAN_STQA.md` | Terpenuhi |
| `NFR-007` | Proteksi XSS & SQL Injection | Blade auto-escaping (0 `{!! !!}`), Eloquent prepared statements; diuji di PHPUnit | Terpenuhi |
| `NFR-008(b),(c)` | Integritas transaksi DB & halaman error ramah saat `APP_DEBUG=false` | `DB::transaction()` di `AdminController` + `bootstrap/app.php` exception renderer -> `errors/generic.blade.php` | Terpenuhi |
| `OR-002` | Pencadangan basis data periodik | Perintah Artisan `php artisan mbg:backup` (`BackupDatabaseCommand.php`) | Terpenuhi |

---

## 4. Catatan Lingkungan & Langkah Lanjutan Lapangan

1. **Pelatihan Model `.joblib` pada Lingkungan Python Produksi**: Pada mesin lokal pengujian saat ini, runtime Python + `scikit-learn`/`joblib` belum terpasang sehingga `PredictionService` otomatis menjalankan mekanisme fallback `FR-032`. Di server produksi SPPG yang memiliki Python 3.10+, jalankan `python scripts/train_evaluate_rf.py` untuk memproduksi `scripts/rf_model.joblib` & `scripts/model_evaluation.json`.
2. **Pilot & UAT Lapangan**: Uji penerimaan langsung bersama Admin dan Ahli Gizi SPPG Ujung Lare dilakukan pada fase serah-terima operasional menggunakan server terpasang.

