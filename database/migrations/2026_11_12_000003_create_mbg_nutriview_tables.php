<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bahan Baku
        Schema::create('bahan_bakus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->string('kode_lot')->unique();
            $table->string('kategori')->default('nasi'); // nasi, lauk, sayur, buah, lainnya
            $table->decimal('stok_kg', 10, 2)->default(0);
            $table->string('satuan')->default('kg');
            $table->boolean('is_utama')->default(false);
            $table->dateTime('tanggal_masuk');
            $table->dateTime('tanggal_kedaluwarsa');
            $table->string('supplier');
            $table->timestamps();
        });

        // 2. Data Gizi Bahan
        Schema::create('data_gizis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan')->unique();
            $table->decimal('basis_berat_gram', 8, 2)->default(100.00);
            $table->decimal('energi_kkal', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('lemak_g', 8, 2)->default(0);
            $table->decimal('karbohidrat_g', 8, 2)->default(0);
            $table->decimal('serat_g', 8, 2)->default(0);
            $table->string('vitamin_mineral')->nullable();
            $table->text('sumber_catatan')->nullable();
            $table->timestamps();
        });

        // 3. Kelompok Penerima & Porsi (Sasaran Agregat)
        Schema::create('kelompok_penerimas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelompok');
            $table->string('kategori')->default('Siswa'); // Siswa, Batita, Balita, Ibu hamil, Ibu menyusui
            $table->integer('jumlah_penerima')->default(0);
            $table->integer('porsi_terkemas')->default(0);
            $table->string('titik_antar');
            $table->integer('estimasi_waktu_tempuh_menit')->default(15);
            $table->timestamps();
        });

        // 4. Menu Makanan
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu');
            $table->date('tanggal_penyajian');
            $table->string('jenis_makanan')->default('Makanan lengkap');
            $table->integer('ukuran_porsi_gram')->default(450);
            $table->integer('jumlah_porsi_rencana')->default(300);
            $table->string('status')->default('draft'); // draft, menunggu_validasi, tervalidasi, terpublikasi
            $table->text('catatan_revisi')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('foto_menu')->nullable();
            $table->foreignId('kelompok_penerima_id')->nullable()->constrained('kelompok_penerimas')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Komposisi Bahan Menu (Pivot menu - bahan_baku)
        Schema::create('menu_bahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('bahan_baku_id')->constrained('bahan_bakus')->cascadeOnDelete();
            $table->decimal('jumlah_batch', 10, 2)->default(0);
            $table->string('satuan')->default('kg');
            $table->string('komponen')->default('Lauk'); // Nasi, Lauk utama, Lauk nabati, Sayur, Buah
            $table->decimal('berat_per_sajian_gram', 8, 2)->default(0);
            $table->timestamps();
        });

        // 6. Jadwal Produksi
        Schema::create('jadwal_produksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('kelompok_penerima_id')->constrained('kelompok_penerimas')->cascadeOnDelete();
            $table->string('batch_no')->default('Tahap 1');
            $table->date('tanggal');
            $table->string('tahap'); // Olah Nasi, Olah Lauk, Olah Sayur, Pemorsian / pengemasan, Distribusi
            $table->time('waktu_mulai_rencana');
            $table->time('waktu_selesai_rencana');
            $table->string('petugas_rencana');
            $table->integer('porsi_target')->default(300);
            $table->time('target_tiba')->nullable();
            $table->string('status')->default('Direncanakan');
            $table->timestamps();
        });

        // 7. Alur Operasional Dapur Real-Time
        Schema::create('alur_operasionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->string('batch_no')->default('Tahap 1');
            $table->date('tanggal');
            $table->string('tahap'); // terima_bahan, pengolahan, pengemasan, pengantaran
            $table->dateTime('waktu_mulai_aktual')->nullable();
            $table->dateTime('waktu_selesai_aktual')->nullable();
            $table->string('petugas_aktual')->nullable();
            $table->string('status_tahap')->default('belum_mulai'); // belum_mulai, selesai, terlambat
            $table->integer('porsi_terkemas')->nullable();
            $table->text('catatan_kendala')->nullable();
            $table->dateTime('waktu_matang_nasi')->nullable();
            $table->dateTime('waktu_matang_lauk')->nullable();
            $table->dateTime('waktu_matang_sayur')->nullable();
            $table->dateTime('waktu_matang_terakhir')->nullable(); // Dasar perhitungan batas 4 jam
            $table->timestamps();
        });

        // 8. Rincian Titik Pengantaran
        Schema::create('pengantaran_titiks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alur_operasional_id')->constrained('alur_operasionals')->cascadeOnDelete();
            $table->foreignId('kelompok_penerima_id')->constrained('kelompok_penerimas')->cascadeOnDelete();
            $table->string('petugas_antar')->nullable();
            $table->integer('porsi_dikirim')->default(0);
            $table->dateTime('waktu_berangkat_aktual')->nullable();
            $table->dateTime('waktu_tiba_aktual')->nullable();
            $table->string('petugas_penerima')->nullable();
            $table->string('status_antar')->default('Belum distribusi');
            $table->timestamps();
        });

        // 9. KPI & Prediksi Risiko Random Forest
        Schema::create('kpi_prediksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->string('batch_no')->default('Tahap 1');
            $table->date('tanggal');
            $table->foreignId('kelompok_penerima_id')->nullable()->constrained('kelompok_penerimas')->nullOnDelete();
            $table->decimal('skor_sr', 5, 2)->nullable(); // Penerimaan bahan
            $table->decimal('skor_sx', 5, 2)->nullable(); // Kedaluwarsa bahan
            $table->decimal('skor_sp', 5, 2)->nullable(); // Mulai pengolahan
            $table->decimal('skor_sq', 5, 2)->nullable(); // Pemorsian/pengemasan
            $table->decimal('skor_sk', 5, 2)->nullable(); // Kapasitas porsi
            $table->decimal('skor_sw', 5, 2)->nullable(); // Waktu tiba
            $table->decimal('skor_sb', 5, 2)->nullable(); // Batas konsumsi 4 jam
            $table->decimal('skor_total', 5, 2)->nullable();
            $table->string('kategori_kpi')->nullable(); // Baik, Perlu perbaikan, Prioritas perbaikan
            $table->string('kelas_prediksi')->nullable(); // Layak, Tidak Layak
            $table->decimal('probabilitas_risiko', 5, 2)->nullable();
            $table->boolean('is_warning_kedaluwarsa')->default(false);
            $table->text('keterangan_keputusan')->nullable();
            $table->timestamps();
        });

        // 10. Audit Log (Append-only sesuai OR-001)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username')->default('sistem');
            $table->string('role')->default('publik');
            $table->string('action'); // login, logout, tambah, ubah, hapus, validasi, publikasi, akses_ditolak
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('kpi_prediksis');
        Schema::dropIfExists('pengantaran_titiks');
        Schema::dropIfExists('alur_operasionals');
        Schema::dropIfExists('jadwal_produksis');
        Schema::dropIfExists('menu_bahans');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('kelompok_penerimas');
        Schema::dropIfExists('data_gizis');
        Schema::dropIfExists('bahan_bakus');
    }
};

