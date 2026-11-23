# Panduan Penggunaan Web MBG NutriView
**Sistem Transparansi Menu, Gizi, dan Alur Dapur — SPPG Ujung Lare, Kota Parepare**

Panduan ini disusun agar **Masyarakat/Orang Tua**, **Admin SPPG**, dan **Ahli Gizi** dapat menggunakan aplikasi **MBG NutriView** dengan mudah dari awal hingga selesai.

---

## Ringkasan 3 Jenis Pengguna

| Peran | Perlu Login? | Tugas Utama di Aplikasi |
| :--- | :---: | :--- |
| **Orang Tua / Sekolah / Masyarakat** | Tidak | Melihat menu hari ini, kandungan gizi, asal bahan baku, dan memantau proses masak hingga pengantaran (atau lewat scan QR). |
| **Admin SPPG** | Ya | Mengelola data menu, stok & masa simpan bahan baku, kelompok penerima, jadwal produksi, pencatatan waktu dapur, dan memantau KPI. |
| **Ahli Gizi** | Ya | Mengelola master nilai gizi (TKPI), menghitung & memvalidasi gizi menu sesuai AKG, serta mempublikasikan menu & Kode QR ke portal publik. |

---

## 1. Panduan untuk Orang Tua, Sekolah & Masyarakat (Portal Publik)

Portal Publik dapat dibuka **langsung tanpa login** melalui alamat utama website atau dengan **memindai Kode QR** pada kotak makanan / lembar distribusi.

### A. Melihat Menu & Kandungan Gizi Hari Ini
1. Buka halaman utama (**Portal Publik**).
2. Jika terdapat lebih dari satu menu yang sedang tayang, pilih menu yang ingin dilihat pada kotak pilihan **"Pilih menu terpublikasi lain"** di bagian atas.
3. Pada bagian utama, Anda dapat langsung melihat:
   - **Nama Menu**, tanggal penyajian, sasaran penerima, dan foto menu.
   - **Takaran Saji** (gram), **Target Produksi** (porsi), dan **Batas Konsumsi Aman** (maksimal 4 jam setelah makanan matang).
   - **Kode QR Terverifikasi** untuk dibagikan atau dipindai dari ponsel lain.
4. Gulir ke bawah ke bagian **Kandungan Gizi per 1 Sajian** untuk melihat rincian **Energi Total (kkal)**, **Protein**, **Lemak Sehat**, **Karbohidrat**, dan **Serat Pangan** per porsi (mengacu pada basis 100g TKPI & standar BGN/AKG).
5. Gulir ke bagian **Transparansi Bahan Baku Penyusun Makanan** untuk melihat daftar bahan, gramasi per porsi, kode lot penyimpanan, dan nama pemasok resmi.

### B. Memantau Proses Masak & Pengantaran (Alur Dapur)
1. Klik tombol **`Alur Dapur`** di bagian atas atau **`Pantau Dapur →`** / **`Lihat rincian ketertelusuran alur`**.
2. Anda akan melihat persentase progres dapur dan status aktual 4 tahapan:
   - **1. Penerimaan Bahan Baku** — pemeriksaan bahan, lot pemasok, dan masa simpan.
   - **2. Pengolahan Makanan (Memasak)** — waktu mulai dan selesai memasak.
   - **3. Pemorsian & Pengemasan** — pengemasan higienis sesuai gramasi AKG.
   - **4. Pengantaran ke Sekolah Sasaran** — status pengantaran hingga batas konsumsi aman 4 jam.
3. Arti warna status:
   - **Selesai Dicatat**: tahap selesai tepat waktu.
   - **Sedang Berlangsung**: tahap sedang dikerjakan tim dapur.
   - **Menunggu Pelaksanaan**: tahap belum dimulai/dicatat.
   - **Selesai Terlambat**: tahap selesai melewati jadwal rencana.
4. Di bagian bawah, tersedia tabel **Jadwal Produksi & Pengantaran Terencana** untuk melihat jam rencana, target tiba di sekolah, dan petugas penanggung jawab.
5. Klik **`← Kembali ke Menu`** untuk kembali ke halaman utama menu.

> **Catatan QR Code:** Jika kode QR yang dipindai rusak atau bukan QR resmi SPPG Ujung Lare, sistem akan menampilkan pesan **"Kode QR Tidak Dikenali"** beserta tombol **`Buka Beranda Portal Publik`**.

---

## 2. Cara Masuk (Login) untuk Petugas SPPG

1. Dari Portal Publik, klik tombol **`Masuk Petugas`** di pojok kanan atas (atau buka halaman `/login`).
2. Masukkan **Username** dan **Kata sandi** yang diberikan oleh koordinator SPPG.
   - Gunakan tombol **`Lihat`** di kolom kata sandi jika ingin memastikan ketikan sudah benar.
   - Jika lupa kata sandi, klik **`Lupa kata sandi?`** untuk melihat arahan hubungi Koordinator Admin SPPG.
3. Klik **`Masuk ke Sistem`**. Sistem otomatis mengarahkan Anda ke dashboard sesuai peran (**Admin** atau **Ahli Gizi**).
4. Untuk keluar setelah selesai bekerja, klik **`Keluar dari akun`** di bagian bawah menu samping (sidebar).

---

## 3. Panduan untuk Admin SPPG

Alur kerja harian Admin SPPG yang disarankan:

### Langkah 1 — Pantau Ringkasan di `Dashboard`
- Cek jumlah **Total penerima manfaat**, **Penerima batch Tahap 1**, dan **Porsi terkemas**.
- Periksa **Status batch hari ini**, **Daftar pekerjaan** yang belum selesai, serta **Peringatan bahan** (bahan yang sudah atau mendekati kedaluwarsa).

### Langkah 2 — Kelola Stok di `Bahan Baku`
1. Buka menu **`Bahan Baku`**.
2. Gunakan kolom pencarian dan filter masa simpan (*Valid/Aman*, *Segera pakai ≤ 2 hari*, *Kedaluwarsa*) untuk memeriksa stok gudang.
3. Untuk menambah bahan baru, isi form **Tambah / Ubah bahan baku** (nama bahan, stok, satuan, kode lot unik, supplier, tanggal masuk & kedaluwarsa), lalu klik **`Simpan Bahan`**.
4. Klik **`Ubah`** pada baris tabel jika ingin memperbarui data bahan.
5. **Penting:** Bahan berstatus **Kedaluwarsa** tidak boleh dipakai dalam pengolahan.

### Langkah 3 — Susun Menu di `Data Menu`
1. Buka menu **`Data Menu`**.
2. Isi nama menu, tanggal penyajian, jenis makanan, kelompok penerima, ukuran porsi (gram), target porsi, dan unggah foto menu (opsional).
3. Pada tabel **Bahan dan jumlah untuk sajian rencana**, pilih bahan baku dari gudang, isi jumlah batch, satuan, komponen (*Nasi / Lauk / Sayur*), dan gramasi per sajian. Klik **`+ Tambah Bahan`** untuk menambah baris.
4. Pilih aksi penyimpanan:
   - **`Simpan sebagai Draft`**: menyimpan sementara tanpa diajukan dulu.
   - **`Simpan & Ajukan Validasi Ahli Gizi`**: mengirim menu ke antrean Ahli Gizi agar segera dihitung dan divalidasi.
5. *Catatan:* Jika menu yang sudah terpublikasi diubah komposisinya, status menu otomatis kembali ke **Menunggu validasi** agar Ahli Gizi memvalidasi ulang sebelum tayang kembali.

### Langkah 4 — Atur Sasaran di `Penerima dan Porsi`
1. Buka menu **`Penerima dan Porsi`**.
2. Tambahkan atau perbarui kelompok penerima (nama sekolah/posyandu, kategori, titik antar, jumlah penerima, jumlah porsi terkemas, dan estimasi waktu tempuh dalam menit).
3. Periksa kotak **Pemeriksaan kecukupan porsi batch** di bagian bawah untuk memastikan tidak ada kekurangan porsi sebelum distribusi.

### Langkah 5 — Susun Rencana di `Jadwal Produksi`
1. Buka menu **`Jadwal Produksi`**.
2. Pilih menu, kelompok penerima, batch, tanggal, tahap, jam mulai–selesai rencana, target tiba, petugas, dan target porsi, lalu klik **`Simpan Jadwal`**.
3. Sistem otomatis menolak jika petugas yang sama dijadwalkan bentrok pada jam yang saling beririsan di hari yang sama.

### Langkah 6 — Catat Waktu Aktual di `Pencatatan Tahap`
1. Buka menu **`Pencatatan Tahap`**.
2. Pilih menu dan batch, lalu klik tahapan yang sedang dikerjakan (*1. Terima bahan*, *2. Olah*, *3. Kemas*, *4. Distribusi*).
3. Isi **Waktu mulai aktual**, **Waktu selesai aktual**, **Petugas**, **Porsi terkemas**, serta **Jam matang komponen** (*Nasi, Lauk, Sayur*) pada tahap pengolahan agar sistem otomatis menghitung batas konsumsi aman 4 jam.
4. Klik **`Simpan Catatan Tahap`**.
5. Setelah tahap distribusi dicatat, isi realisasi **Porsi dikirim**, **Berangkat/Tiba aktual**, **Petugas penerima di sekolah**, dan **Status antar** pada tabel **Porsi dan penerimaan per titik antar**.

### Langkah 7 — Evaluasi di `KPI dan Prediksi Risiko`
- Buka menu **`KPI dan Prediksi Risiko`** untuk melihat skor 7 indikator KPI dan hasil prediksi kelayakan konsumsi per titik antar.
- Hasil prediksi membantu petugas mengambil keputusan lebih cepat apabila ada risiko keterlambatan mendekati batas aman 4 jam.

---

## 4. Panduan untuk Ahli Gizi

### Langkah 1 — Kelola Master Rujukan di `Dashboard Ahli Gizi / Data Gizi`
1. Lihat ringkasan jumlah menu yang **Menunggu perhitungan**, **Menunggu validasi**, dan **Tervalidasi / Terpublikasi**.
2. Pada bagian **Master data gizi bahan**, gunakan kolom pencarian untuk mengecek rujukan nilai gizi (basis 100g TKPI).
3. Jika ada bahan baru yang belum memiliki nilai gizi, isi form **Tambah / Ubah data gizi bahan** (nama bahan, basis berat, energi, protein, lemak, karbohidrat, serat, vitamin/mineral, dan sumber rujukan), lalu klik **`Simpan Data Gizi`**.
   - *Tips:* Pastikan ejaan nama bahan sama dengan nama di modul Bahan Baku agar perhitungan gizi menu berjalan otomatis.

### Langkah 2 — Validasi & Publikasikan Menu di `Hitung & Validasi`
1. Buka menu **`Hitung & Validasi`** (atau klik tautan tindakan pada tabel **Antrean menu** di Dashboard).
2. Pilih menu yang akan diperiksa.
3. Periksa tabel komposisi bahan dan perbandingan **Hasil Hitung Gizi** terhadap **Target AKG**.
4. Ambil keputusan validasi:
   - Jika sudah sesuai standar AKG, klik **`Validasi Menu`**.
   - Jika komposisi perlu diperbaiki Admin, isi **Catatan Ahli Gizi** (wajib) lalu klik **`Minta Revisi`**.
5. Setelah status menu menjadi **Tervalidasi**, klik **`Publikasikan Sekarang`** agar menu langsung tayang di Portal Publik beserta **Kode QR** resminya.
6. Anda dapat klik **`Preview Portal`** untuk mengecek tampilan publik, **`Salin Tautan`** untuk membagikan link, atau **`Tarik Publikasi`** jika menu perlu diturunkan sementara.

### Langkah 3 — Pantau Data Operasional (Mode Hanya Lihat)
- **`Menu dan Bahan`**: melihat daftar menu dan rincian bahan yang diinput Admin tanpa mengubah data.
- **`KPI dan Prediksi`**: memantau skor KPI dan prediksi risiko keterlambatan distribusi secara *read-only*.

---

## 5. Pertanyaan Umum (FAQ)

- **Apakah orang tua harus membuat akun untuk melihat menu?**
  Tidak. Orang tua dan pihak sekolah cukup membuka halaman utama Portal Publik atau memindai Kode QR.
- **Kenapa tombol "Publikasikan Sekarang" tidak bisa diklik oleh Ahli Gizi?**
  Karena menu wajib melalui tahap **Validasi Menu** terlebih dahulu hingga berstatus *Tervalidasi*.
- **Bagaimana jika ada teks atau tampilan belum berubah setelah pembaruan?**
  Tekan **`Ctrl + F5`** di browser untuk memuat ulang halaman secara penuh.
- **Bagaimana cara melihat Portal Publik dari halaman Admin/Ahli Gizi?**
  Klik tautan **`Portal publik`** di bagian kiri bawah sidebar.

