# Panduan Pengguna MBG NutriView (SPPG Ujung Lare - Kota Parepare)

Dokumen ini menjelaskan cara penggunaan aplikasi web **MBG NutriView** untuk tiga kategori pengguna: **Publik / Sekolah / Orang Tua**, **Admin SPPG**, dan **Ahli Gizi**.

---

## 1. Akses Portal Publik (Tanpa Login)

Portal publik bersifat hanya-baca (*read-only*) dan menampilkan informasi menu yang sudah dipublikasikan oleh Ahli Gizi.

1. **Menu Hari Ini & Informasi Gizi (`/`)**
   - Memilih menu aktif (default: menu terbaru dengan status `terpublikasi`) atau memilih dari daftar menu tersaji.
   - Melihat rincian **Energi (kkal), Protein (g), Lemak (g), Karbohidrat (g)** per sajian beserta status kecukupan sesuai standar AKG.
   - Melihat daftar komponen bahan baku penyusun menu (nasi, lauk, sayur, buah) dan gramasi per sajian.
   - Memindai atau menggunakan **QR Code SVG offline** untuk mengakses tautan cepat menu terkait.
2. **Pemindaian QR Code (`/scan?token=...`)**
   - Memindai QR menu (`token=mbg-menu-<id>`) langsung mengarahkan pengguna ke rincian gizi menu tersebut.
   - Jika QR tidak valid atau menu telah ditarik publikasinya (`status != terpublikasi`), halaman peringatan aman akan ditampilkan tanpa menampilkan menu tersembunyi.
3. **Alur Operasional & Jadwal Terencana (`/alur-operasional`)**
   - Menampilkan garis waktu 4 tahap operasional: **Penerimaan Bahan Baku**, **Pengolahan**, **Pemorsian & Pengemasan**, dan **Pengantaran ke Sekolah Sasaran** beserta status aktual (`Selesai Dicatat`, `Sedang Berlangsung`, `Selesai Terlambat`, atau `Menunggu Pelaksanaan`).
   - Menampilkan tabel **Jadwal Produksi & Pengantaran Terencana** (tahap, batch, kelompok penerima, jam rencana, target tiba, dan petugas penanggung jawab).

---

## 2. Alur Kerja Admin SPPG (`role: admin`)

Masuk melalui halaman `/login` menggunakan akun Admin (contoh akun simulasi: `admin` / password terkonfigurasi di seeder).

1. **Dashboard Admin (`/admin/dashboard`)**
   - Memantau ringkasan menu aktif, jadwal hari ini, ketersediaan bahan baku, tahap produksi terkini, dan ringkasan skor KPI.
2. **Menu & Bahan Baku (`/admin/menu-bahan`)**
   - **Tambah / Ubah Menu**: Mengisi nama menu, tanggal penyajian, jenis makanan, ukuran porsi (min. 50 gram), jumlah porsi rencana, kelompok penerima, foto menu opsional, dan daftar komposisi bahan per batch/per sajian.
   - **Ajukan Validasi Gizi**: Mengirim menu dari `draft` ke status `menunggu_validasi`.
   - *Catatan*: Apabila komposisi bahan pada menu yang sudah `tervalidasi` atau `terpublikasi` diubah, sistem otomatis mengembalikan status ke `menunggu_validasi` dan mencabut publikasi sementara (FR-007).
3. **Bahan Baku & Traceability (`/admin/bahan-baku`)**
   - Mengelola stok bahan baku beserta **kode lot unik**, **supplier**, **tanggal masuk**, dan **tanggal kedaluwarsa** (`stok_kg > 0` dan `tanggal_kedaluwarsa` harus setelah `tanggal_masuk`).
   - Pantau indikator peringatan bahan mendekati kedaluwarsa (<= 2 hari) atau sudah kedaluwarsa.
4. **Penerima & Porsi (`/admin/penerima-porsi`)**
   - Mengelola daftar sekolah / sasaran penerima MBG beserta jumlah penerima, target porsi terkemas, titik antar, dan estimasi waktu tempuh (menit).
5. **Jadwal Produksi & Pengantaran (`/admin/jadwal-produksi`)**
   - Menyusun jadwal per tahap, batch, kelompok penerima, petugas rencana, jam mulai–selesai rencana, dan target tiba.
   - Sistem otomatis mendeteksi **bentrok jadwal petugas** pada tanggal & rentang jam yang bertumpukan (FR-022).
6. **Pencatatan Tahap Operasional (`/admin/pencatatan-tahap`)**
   - Mengisi realisasi setiap tahap (`terima_bahan`, `pengolahan`, `pengemasan`, `pengantaran`) beserta **waktu mulai aktual**, **waktu selesai aktual**, **petugas aktual**, porsi terkemas, serta waktu matang komponen nasi/lauk/sayur pada tahap pengolahan.
   - Waktu selesai yang melampaui target jadwal (atau batas standar Petunjuk Teknis BGN) + toleransi `15 menit` otomatis ditandai **TERLAMBAT** dan memengaruhi indikator KPI terkait.
   - Pengisian tahap `pengemasan` otomatis memicu prediksi kelayakan konsumsi batas 4 jam; pengisian tahap `pengantaran` otomatis menghitung ulang skor KPI gabungan.
   - Memperbarui realisasi pengantaran per sekolah sasaran (`Petugas Antar`, `Porsi Dikirim`, `Waktu Berangkat`, `Waktu Tiba`, `Petugas Penerima`, `Status Antar`).
7. **KPI & Prediksi Risiko (`/admin/kpi-prediksi`)**
   - Melihat rincian 7 indikator KPI (`Sr`, `Sx`, `Sp`, `Sq`, `Sk`, `Sw`, `Sb`), **Skor Total Berbobot**, kategori (`Baik`, `Perlu perbaikan`, `Prioritas perbaikan`), klasifikasi prediksi kelayakan (`Layak` / `Tidak Layak`), probabilitas risiko, dan tabel evaluasi per titik sekolah beserta sisa menit aman menuju batas 4 jam (240 menit).

---

## 3. Alur Kerja Ahli Gizi (`role: ahli_gizi`)

Masuk melalui halaman `/login` menggunakan akun Ahli Gizi (`ahli_gizi`).

1. **Dashboard & Referensi Data Gizi (`/gizi/dashboard`)**
   - Mengelola master data komposisi gizi per 100 gram untuk setiap bahan baku (Energi kkal, Protein g, Lemak g, Karbohidrat g).
2. **Hitung & Validasi Gizi (`/gizi/hitung-validasi`)**
   - Memilih menu yang diajukan Admin untuk meninjau hasil perhitungan otomatis kandungan gizi per sajian dan kelengkapan data gizi seluruh komponen bahan.
   - **Validasi (`tervalidasi`)**: Hanya dapat disetujui apabila seluruh bahan penyusun pada menu sudah memiliki referensi data gizi per 100g.
   - **Kembalikan Revisi (`revisi`)**: Wajib menyertakan `catatan_revisi` yang jelas untuk Admin SPPG.
   - **Publikasikan / Tarik Publikasi**: Mengaktifkan menu tervalidasi agar tampil di Portal Publik beserta QR Code-nya, atau menarik kembali publikasi sewaktu-waktu bila ditemukan ketidaksesuaian.
3. **Akses Hanya-Baca Menu & KPI (`/gizi/menu-bahan`, `/gizi/kpi-prediksi`)**
   - Meninjau data menu, komposisi bahan, serta laporan KPI & prediksi kelayakan tanpa mengubah data operasional dapur.

