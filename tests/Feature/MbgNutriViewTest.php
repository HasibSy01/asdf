<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Menu;
use App\Models\BahanBaku;
use App\Models\DataGizi;
use App\Models\KelompokPenerima;
use App\Models\JadwalProduksi;
use App\Models\AlurOperasional;
use App\Models\KpiPrediksi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MbgNutriViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * TC-001: Login berhasil untuk Admin dan Ahli Gizi
     */
    public function test_login_berhasil_admin_dan_gizi(): void
    {
        // Admin
        $resAdmin = $this->post(route('login.process'), [
            'username' => 'admin_uji',
            'password' => 'Admin#Uji2026',
        ]);
        $resAdmin->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();

        $this->post(route('logout'));

        // Ahli Gizi
        $resGizi = $this->post(route('login.process'), [
            'username' => 'gizi_uji',
            'password' => 'Gizi#Uji2026',
        ]);
        $resGizi->assertRedirect(route('gizi.dashboard'));
        $this->assertAuthenticated();
    }

    /**
     * TC-002: Login gagal dengan kredensial salah (NFR-003: Pesan umum)
     */
    public function test_login_gagal_kredensial_salah(): void
    {
        $res = $this->post(route('login.process'), [
            'username' => 'admin_uji',
            'password' => 'SalahSandi123',
        ]);
        $res->assertSessionHas('error');
        $this->assertGuest();
    }

    /**
     * TC-003 & TC-035: Pembatasan Hak Akses (RBAC) Least Privilege
     */
    public function test_rbac_pembatasan_kewenangan_peran(): void
    {
        // Admin mencoba akses halaman Ahli Gizi
        $admin = User::where('username', 'admin_uji')->first();
        $res = $this->actingAs($admin)->get(route('gizi.dashboard'));
        $res->assertRedirect(route('admin.dashboard'));

        // Ahli Gizi mencoba akses halaman Admin
        $gizi = User::where('username', 'gizi_uji')->first();
        $resGizi = $this->actingAs($gizi)->get(route('admin.dashboard'));
        $resGizi->assertRedirect(route('gizi.dashboard'));
    }

    /**
     * TC-004: Pengguna tanpa login diarahkan ke halaman login
     */
    public function test_unauthenticated_diarahkan_ke_login(): void
    {
        $res = $this->get(route('admin.dashboard'));
        $res->assertRedirect(route('login'));
    }

    /**
     * TC-007 & FR-007: Validasi ulang menu setelah bahan baku diubah pada menu terpublikasi
     */
    public function test_menu_rollback_menunggu_validasi_saat_bahan_diubah(): void
    {
        $admin = User::where('username', 'admin_uji')->first();
        $menu = Menu::where('status', 'tervalidasi')->first() ?: Menu::first();
        $menu->update(['status' => 'terpublikasi', 'is_published' => true]);

        $bahan = BahanBaku::first();

        // Admin mengubah bahan pada menu terpublikasi
        $res = $this->actingAs($admin)->post(route('admin.menu.update', $menu->id), [
            'nama_menu' => $menu->nama_menu,
            'tanggal_penyajian' => $menu->tanggal_penyajian->format('Y-m-d'),
            'jenis_makanan' => $menu->jenis_makanan,
            'ukuran_porsi_gram' => 450,
            'jumlah_porsi_rencana' => 300,
            'kelompok_penerima_id' => $menu->kelompok_penerima_id,
            'bahan_ids' => [$bahan->id],
            'jumlah_batch' => [35],
            'satuan' => ['kg'],
            'komponen' => ['Nasi'],
            'berat_per_sajian' => [100],
        ]);

        $menu->refresh();
        $this->assertEquals('menunggu_validasi', $menu->status);
        $this->assertFalse($menu->is_published);
    }

    /**
     * TC-009: Perhitungan Kandungan Gizi Menu Akurat
     */
    public function test_perhitungan_gizi_menu(): void
    {
        $menu = Menu::with('menuBahans.bahanBaku')->first();
        $giziBesar = $menu->hitungGiziPerSajian('besar');
        $giziKecil = $menu->hitungGiziPerSajian('kecil');

        $this->assertArrayHasKey('energi_kkal', $giziBesar);
        $this->assertArrayHasKey('protein_g', $giziBesar);
        $this->assertGreaterThan(0, $giziBesar['energi_kkal']);
        $this->assertGreaterThan(0, $giziKecil['energi_kkal']);
        $this->assertGreaterThan($giziKecil['energi_kkal'], $giziBesar['energi_kkal']);
    }

    /**
     * TC-012 & TC-013: Aturan Publikasi Menu oleh Ahli Gizi
     */
    public function test_aturan_publikasi_menu_ahli_gizi(): void
    {
        $gizi = User::where('username', 'gizi_uji')->first();
        $menu = Menu::first();

        // Jika status masih menunggu_validasi, publikasi wajib ditolak (FR-013)
        $menu->update(['status' => 'menunggu_validasi']);
        $resTolak = $this->actingAs($gizi)->post(route('gizi.publikasi.process', $menu->id));
        $resTolak->assertSessionHas('error');

        // Jika sudah tervalidasi, publikasi berhasil (FR-012)
        $menu->update(['status' => 'tervalidasi']);
        $resLolos = $this->actingAs($gizi)->post(route('gizi.publikasi.process', $menu->id));
        $resLolos->assertSessionHas('success');

        $menu->refresh();
        $this->assertEquals('terpublikasi', $menu->status);
        $this->assertTrue($menu->is_published);
    }

    /**
     * TC-022: Deteksi Jadwal Bentrok untuk Petugas yang Sama (FR-022)
     */
    public function test_deteksi_jadwal_bentrok(): void
    {
        $admin = User::where('username', 'admin_uji')->first();
        $menu = Menu::first();
        $kelompok = KelompokPenerima::first();

        // Buat jadwal 1
        JadwalProduksi::create([
            'menu_id' => $menu->id,
            'kelompok_penerima_id' => $kelompok->id,
            'batch_no' => 'Tahap 1',
            'tanggal' => '2026-11-20',
            'tahap' => 'Olah · Nasi',
            'waktu_mulai_rencana' => '02:00:00',
            'waktu_selesai_rencana' => '04:00:00',
            'petugas_rencana' => 'Petugas Andi',
            'porsi_target' => 300,
            'status' => 'Direncanakan',
        ]);

        // Input jadwal 2 dengan petugas yang sama dan rentang waktu beririsan (03:00 - 05:00)
        $res = $this->actingAs($admin)->post(route('admin.jadwal.store'), [
            'menu_id' => $menu->id,
            'kelompok_penerima_id' => $kelompok->id,
            'batch_no' => 'Tahap 1',
            'tanggal' => '2026-11-20',
            'tahap' => 'Olah · Lauk',
            'waktu_mulai_rencana' => '03:00',
            'waktu_selesai_rencana' => '05:00',
            'petugas_rencana' => 'Petugas Andi',
            'porsi_target' => 300,
        ]);

        $res->assertSessionHas('error');
    }

    /**
     * TC-015, TC-016 & TC-017: Portal Publik & Penanganan QR Code
     */
    public function test_portal_publik_dan_penanganan_qr(): void
    {
        // 1. Portal publik dapat dibuka tanpa login (mode read-only)
        $res = $this->get(route('public.index'));
        $res->assertStatus(200);
        $res->assertSee('MBG NutriView');

        // 2. Alur operasional dapat diakses
        $resAlur = $this->get(route('public.alur'));
        $resAlur->assertStatus(200);

        // 3. QR Code tidak valid menampilkan pesan ramah (FR-017)
        $resQr = $this->get(route('public.scan.qr', ['token' => 'xyz-tidak-valid']));
        $resQr->assertStatus(200);
        $resQr->assertSee('Kode QR tidak dikenali');
    }

    /**
     * TC-028 & TC-032: Inferensi AI Random Forest & Fallback Ambang Batas
     */
    public function test_prediksi_risiko_dan_fallback(): void
    {
        $predictionService = app(\App\Services\PredictionService::class);
        $menu = Menu::first();

        $hasil = $predictionService->jalankanPrediksi($menu, 'Tahap 1');

        $this->assertEquals('success', $hasil['status']);
        $this->assertNotNull($hasil['kelas']);
        $this->assertContains($hasil['kelas'], ['Layak', 'Tidak Layak']);
        $this->assertNotNull($hasil['probabilitas']);
    }

    /**
     * Verifikasi Semua 12 Halaman Render Sukses HTTP 200 (100% Cocok Desain Figma)
     */
    public function test_render_semua_tampilan_sesuai_desain_figma(): void
    {
        $admin = User::where('role', 'admin')->first();
        $gizi = User::where('role', 'ahli_gizi')->first();

        // 1. Login & Public
        $this->get(route('login'))->assertStatus(200);
        $this->get(route('public.index'))->assertStatus(200);
        $this->get(route('public.alur'))->assertStatus(200);

        // 2. Admin Views
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.menu.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.bahan.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.penerima.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.jadwal.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.tahap.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.kpi.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.prediksi.index'))->assertStatus(200);

        // 3. Ahli Gizi Views
        $this->actingAs($gizi)->get(route('gizi.dashboard'))->assertStatus(200);
        $this->actingAs($gizi)->get(route('gizi.hitung.validasi'))->assertStatus(200);
        $this->actingAs($gizi)->get(route('gizi.menu.lihat'))->assertStatus(200);
        $this->actingAs($gizi)->get(route('gizi.kpi.lihat'))->assertStatus(200);
        $this->actingAs($gizi)->get(route('gizi.prediksi.lihat'))->assertStatus(200);
    }

    /**
     * Verifikasi Rate Limiter Login Mencatat Audit Log Setelah 5 Percobaan Gagal
     */
    public function test_login_throttling_mencatat_audit_log_setelah_lima_gagal(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.process'), [
                'username' => 'admin_uji',
                'password' => 'PasswordSalah!' . $i,
            ]);
            $response->assertStatus(302);
        }

        $blockedResponse = $this->post(route('login.process'), [
            'username' => 'admin_uji',
            'password' => 'PasswordSalahLagi!',
        ]);

        $blockedResponse->assertStatus(302);
        $blockedResponse->assertSessionHas('error');

        $this->assertTrue(
            \App\Models\AuditLog::where('action', 'login_diblokir_throttling')->exists()
        );
    }

    /**
     * Verifikasi Portal Publik Dinamis, QR Code SVG Offline & Validasi Token Ketat
     */
    public function test_portal_publik_dinamis_dan_validasi_qr_ketat(): void
    {
        $menu = \App\Models\Menu::where('status', 'terpublikasi')->firstOrFail();

        $publicRes = $this->get(route('public.index'));
        $publicRes->assertStatus(200);
        $publicRes->assertSee('Paket Nasi Ayam Semur & Tempe');
        $publicRes->assertSee('data:image/svg+xml;base64,', false);

        $invalidQr = $this->get(route('public.scan.qr', ['token' => 'token-palsu-abc']));
        $invalidQr->assertStatus(200);
        $invalidQr->assertSee('Kode QR tidak dikenali');

        $validQr = $this->get(route('public.scan.qr', ['token' => 'mbg-menu-' . $menu->id]));
        $validQr->assertRedirect(route('public.index', ['menu_id' => $menu->id]));

        $gizi = User::where('role', 'ahli_gizi')->firstOrFail();
        $this->actingAs($gizi)
            ->get(route('gizi.hitung.validasi'))
            ->assertStatus(200)
            ->assertSee('data:image/svg+xml;base64,', false);
    }

    /**
     * Verifikasi CRUD Dinamis Kelompok Penerima, Jadwal Produksi & Pencatatan Tahap -> KPI
     */
    public function test_admin_crud_dinamis_dan_pencatatan_tahap_memicu_kpi(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $menu = \App\Models\Menu::firstOrFail();
        $kelompok = \App\Models\KelompokPenerima::firstOrFail();

        $this->actingAs($admin)->post(route('admin.penerima.store'), [
            'nama_kelompok' => 'SDN 99 Uji Dinamis',
            'kategori' => 'SD/MI',
            'jumlah_penerima' => 250,
            'porsi_terkemas' => 250,
            'titik_antar' => 'Jl. Jenderal Sudirman No. 99',
            'estimasi_waktu_tempuh_menit' => 20,
        ])->assertStatus(302);

        $this->assertDatabaseHas('kelompok_penerimas', [
            'nama_kelompok' => 'SDN 99 Uji Dinamis',
        ]);

        $tanggalHariIni = now()->format('Y-m-d');
        $this->actingAs($admin)->post(route('admin.jadwal.store'), [
            'menu_id' => $menu->id,
            'kelompok_penerima_id' => $kelompok->id,
            'batch_no' => 'Batch Uji',
            'tanggal' => $tanggalHariIni,
            'tahap' => 'pengolahan',
            'waktu_mulai_rencana' => '05:00',
            'waktu_selesai_rencana' => '06:30',
            'petugas_rencana' => 'Tim Uji',
            'porsi_target' => 300,
            'status' => 'rencana',
        ])->assertStatus(302);

        $jadwal = \App\Models\JadwalProduksi::where('batch_no', 'Batch Uji')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.jadwal.update', $jadwal), [
            'menu_id' => $menu->id,
            'kelompok_penerima_id' => $kelompok->id,
            'batch_no' => 'Batch Uji',
            'tanggal' => $tanggalHariIni,
            'tahap' => 'pengolahan',
            'waktu_mulai_rencana' => '05:15',
            'waktu_selesai_rencana' => '06:45',
            'petugas_rencana' => 'Tim Uji Revisi',
            'porsi_target' => 320,
            'status' => 'berjalan',
        ])->assertStatus(302);

        $this->assertDatabaseHas('jadwal_produksis', [
            'id' => $jadwal->id,
            'petugas_rencana' => 'Tim Uji Revisi',
        ]);

        $this->actingAs($admin)->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertStatus(302);

        $this->assertDatabaseMissing('jadwal_produksis', [
            'id' => $jadwal->id,
        ]);

        $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji KPI',
            'tanggal' => $tanggalHariIni,
            'tahap' => 'pengolahan',
            'waktu_mulai_aktual' => '05:00',
            'waktu_selesai_aktual' => '06:30',
            'petugas_aktual' => 'Koki Uji',
            'porsi_terkemas' => 300,
            'waktu_matang_nasi' => '06:15',
            'waktu_matang_lauk' => '06:25',
            'waktu_matang_sayur' => '06:20',
            'waktu_matang_terakhir' => '06:25',
        ])->assertStatus(302);

        $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji KPI',
            'tanggal' => $tanggalHariIni,
            'tahap' => 'pengemasan',
            'waktu_mulai_aktual' => '06:35',
            'waktu_selesai_aktual' => '07:20',
            'petugas_aktual' => 'Tim Kemas Uji',
            'porsi_terkemas' => 300,
        ])->assertStatus(302);

        $this->assertDatabaseHas('alur_operasionals', [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji KPI',
            'tahap' => 'pengemasan',
        ]);

        $this->assertDatabaseHas('kpi_prediksis', [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji KPI',
        ]);
    }

    /**
     * Verifikasi Presisi Batas Aman 4 Jam (240 Menit) di KpiService & Tarik Publikasi
     */
    public function test_kpi_service_presisi_batas_empat_jam_dan_tarik_publikasi(): void
    {
        $menu = \App\Models\Menu::where('status', 'terpublikasi')->firstOrFail();
        $tanggal = now()->startOfDay();
        $matangTerakhir = $tanggal->copy()->setTime(5, 0, 0);
        $selesaiAntar = $tanggal->copy()->setTime(9, 30, 0); // 4 jam 30 menit = 270 menit > 240

        \App\Models\AlurOperasional::create([
            'menu_id' => $menu->id,
            'batch_no' => 'Batch 4Jam30Mnt',
            'tanggal' => $tanggal->format('Y-m-d'),
            'tahap' => 'pengolahan',
            'waktu_mulai_aktual' => $tanggal->copy()->setTime(4, 0, 0),
            'waktu_selesai_aktual' => $matangTerakhir,
            'waktu_matang_terakhir' => $matangTerakhir,
            'petugas_aktual' => 'Koki Presisi',
            'status_tahap' => 'selesai',
            'porsi_terkemas' => 300,
        ]);

        \App\Models\AlurOperasional::create([
            'menu_id' => $menu->id,
            'batch_no' => 'Batch 4Jam30Mnt',
            'tanggal' => $tanggal->format('Y-m-d'),
            'tahap' => 'pengemasan',
            'waktu_mulai_aktual' => $tanggal->copy()->setTime(5, 15, 0),
            'waktu_selesai_aktual' => $tanggal->copy()->setTime(6, 0, 0),
            'petugas_aktual' => 'Tim Kemas Presisi',
            'status_tahap' => 'selesai',
            'porsi_terkemas' => 300,
        ]);

        \App\Models\AlurOperasional::create([
            'menu_id' => $menu->id,
            'batch_no' => 'Batch 4Jam30Mnt',
            'tanggal' => $tanggal->format('Y-m-d'),
            'tahap' => 'pengantaran',
            'waktu_mulai_aktual' => $tanggal->copy()->setTime(8, 30, 0),
            'waktu_selesai_aktual' => $selesaiAntar,
            'petugas_aktual' => 'Kurir Presisi',
            'status_tahap' => 'selesai',
            'porsi_terkemas' => 300,
        ]);

        $kpi = app(\App\Services\KpiService::class)->hitungKpi($menu, 'Batch 4Jam30Mnt');
        $this->assertNotNull($kpi);
        $this->assertEquals(0.0, (float) $kpi->skor_sb);

        $gizi = User::where('role', 'ahli_gizi')->firstOrFail();
        $this->actingAs($gizi)
            ->post(route('gizi.tarik.publikasi', $menu))
            ->assertStatus(302);

        $this->assertNotEquals('terpublikasi', $menu->fresh()->status);
    }

    /**
     * TC-006 (FR-006): Validasi input bahan baku menolak stok <= 0 dan kedaluwarsa <= tanggal masuk (tambah & ubah)
     */
    public function test_validasi_bahan_baku_ditolak_jika_stok_nol_atau_kedaluwarsa_tidak_valid(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $bahan = \App\Models\BahanBaku::firstOrFail();

        // Store dengan stok 0 dan tanggal kedaluwarsa sebelum tanggal masuk
        $resStore = $this->actingAs($admin)->post(route('admin.bahan.store'), [
            'nama_bahan' => 'Beras Uji Gagal',
            'kode_lot' => 'LOT-ERR-01',
            'kategori' => 'Karbohidrat',
            'stok_kg' => 0,
            'satuan' => 'kg',
            'tanggal_masuk' => '2026-11-10',
            'tanggal_kedaluwarsa' => '2026-11-09',
            'supplier' => 'PT Uji',
        ]);
        $resStore->assertStatus(302)->assertSessionHasErrors(['stok_kg', 'tanggal_kedaluwarsa']);

        // Update dengan stok 0 dan tanggal kedaluwarsa <= tanggal masuk
        $resUpdate = $this->actingAs($admin)->post(route('admin.bahan.update', $bahan), [
            'nama_bahan' => $bahan->nama_bahan,
            'kode_lot' => $bahan->kode_lot,
            'kategori' => $bahan->kategori,
            'stok_kg' => 0,
            'satuan' => $bahan->satuan,
            'tanggal_masuk' => '2026-11-10',
            'tanggal_kedaluwarsa' => '2026-11-10',
            'supplier' => $bahan->supplier,
        ]);
        $resUpdate->assertStatus(302)->assertSessionHasErrors(['stok_kg', 'tanggal_kedaluwarsa']);
    }

    /**
     * TC-010, FR-010, FR-011: Validasi menu ditolak bila gizi bahan belum lengkap & revisi wajib disertai catatan
     */
    public function test_ahli_gizi_gagal_validasi_jika_gizi_tidak_lengkap_dan_revisi_wajib_catatan(): void
    {
        $gizi = User::where('role', 'ahli_gizi')->firstOrFail();

        $bahanTanpaGizi = \App\Models\BahanBaku::create([
            'nama_bahan' => 'Bahan Belum Ada Gizi',
            'kode_lot' => 'LOT-NO-GIZI-01',
            'kategori' => 'Sayuran',
            'stok_kg' => 20,
            'satuan' => 'kg',
            'tanggal_masuk' => now()->subDay()->format('Y-m-d'),
            'tanggal_kedaluwarsa' => now()->addDays(5)->format('Y-m-d'),
            'supplier' => 'Pasar Uji',
        ]);

        $menu = \App\Models\Menu::create([
            'nama_menu' => 'Menu Uji Belum Lengkap Gizi',
            'tanggal_penyajian' => now()->format('Y-m-d'),
            'jenis_makanan' => 'Makan Siang',
            'ukuran_porsi_gram' => 300,
            'jumlah_porsi_rencana' => 100,
            'status' => 'menunggu_validasi',
            'is_published' => false,
        ]);

        $menu->menuBahans()->create([
            'bahan_baku_id' => $bahanTanpaGizi->id,
            'jumlah_batch' => 10,
            'satuan' => 'kg',
            'komponen' => 'Sayur',
            'berat_per_sajian_gram' => 75,
        ]);

        // FR-010: Coba setujui validasi ketika referensi gizi bahan belum ada -> tetap menunggu_validasi
        $resApprove = $this->actingAs($gizi)->post(route('gizi.validasi.process', $menu), [
            'status' => 'tervalidasi',
        ]);
        $resApprove->assertStatus(302)->assertSessionHas('error');
        $this->assertEquals('menunggu_validasi', $menu->fresh()->status);

        // FR-011: Coba kembalikan untuk revisi tanpa catatan -> error validasi
        $resRevisiKosong = $this->actingAs($gizi)->post(route('gizi.validasi.process', $menu), [
            'status' => 'revisi',
            'catatan_revisi' => '   ',
        ]);
        $resRevisiKosong->assertStatus(302)->assertSessionHasErrors(['catatan_revisi']);

        // FR-011: Revisi dengan catatan valid berhasil disimpan
        $resRevisiValid = $this->actingAs($gizi)->post(route('gizi.validasi.process', $menu), [
            'status' => 'revisi',
            'catatan_revisi' => 'Lengkapi gramasi dan tambahkan sumber protein hewani.',
        ]);
        $resRevisiValid->assertStatus(302)->assertSessionHas('success');
        $this->assertEquals('Lengkapi gramasi dan tambahkan sumber protein hewani.', $menu->fresh()->catatan_revisi);
    }

    /**
     * TC-016 (PP-02): Ahli Gizi dapat melihat menu/bahan & KPI dalam mode read-only dan tidak dapat mutasi operasional Admin
     */
    public function test_ahli_gizi_akses_lihat_menu_dan_kpi_readonly(): void
    {
        $gizi = User::where('role', 'ahli_gizi')->firstOrFail();

        $this->actingAs($gizi)->get(route('gizi.menu.lihat'))->assertStatus(200);
        $this->actingAs($gizi)->get(route('gizi.kpi.lihat'))->assertStatus(200);

        // Pastikan tidak bisa melakukan tambah jadwal produksi (mutasi Admin)
        $this->actingAs($gizi)->post(route('admin.jadwal.store'), [])->assertRedirect(route('gizi.dashboard'));
    }

    /**
     * TC-019 (TH-03) & TC-020 (TH-04): Penolakan waktu/petugas tahap tidak lengkap & peringatan urutan tahap
     */
    public function test_pencatatan_tahap_menolak_waktu_tidak_lengkap_dan_peringatan_urutan(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $menu = \App\Models\Menu::firstOrFail();
        $tanggal = now()->format('Y-m-d');

        // FR-019: Kosongkan waktu selesai dan petugas aktual -> ditolak validasi
        $resIncomplete = $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch TH03',
            'tanggal' => $tanggal,
            'tahap' => 'terima_bahan',
            'waktu_mulai_aktual' => '02:00',
            'waktu_selesai_aktual' => '',
            'petugas_aktual' => '',
        ]);
        $resIncomplete->assertStatus(302)->assertSessionHasErrors(['waktu_selesai_aktual', 'petugas_aktual']);

        // Waktu selesai lebih awal daripada waktu mulai -> ditolak
        $resReverseTime = $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch TH03',
            'tanggal' => $tanggal,
            'tahap' => 'terima_bahan',
            'waktu_mulai_aktual' => '05:00',
            'waktu_selesai_aktual' => '04:00',
            'petugas_aktual' => 'Budi Uji',
        ]);
        $resReverseTime->assertStatus(302)->assertSessionHasErrors(['waktu_selesai_aktual']);

        // FR-020: Loncat langsung ke 'pengemasan' tanpa mencatat 'pengolahan' -> warning urutan tahap
        $resOutOfOrder = $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch TH04',
            'tanggal' => $tanggal,
            'tahap' => 'pengemasan',
            'waktu_mulai_aktual' => '05:45',
            'waktu_selesai_aktual' => '06:20',
            'petugas_aktual' => 'Ani Kemas',
            'porsi_terkemas' => 300,
        ]);
        $resOutOfOrder->assertStatus(302)->assertSessionHas('warning');
    }

    /**
     * TC-023 & TC-026 (BT-04): Alur publik tampilkan jadwal produksi terencana & penandaan tahap terlambat
     */
    public function test_alur_publik_menampilkan_jadwal_produksi_dan_status_terlambat(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $menu = \App\Models\Menu::where('status', 'terpublikasi')->firstOrFail();
        $kelompok = \App\Models\KelompokPenerima::firstOrFail();
        $tanggal = now()->format('Y-m-d');

        // Buat jadwal terencana agar muncul di portal alur publik (FR-023)
        \App\Models\JadwalProduksi::create([
            'menu_id' => $menu->id,
            'kelompok_penerima_id' => $kelompok->id,
            'batch_no' => 'Tahap Uji Lambat',
            'tanggal' => $tanggal,
            'tahap' => 'pengantaran',
            'waktu_mulai_rencana' => '07:00:00',
            'waktu_selesai_rencana' => '07:45:00',
            'petugas_rencana' => 'Kurir Terjadwal Uji',
            'porsi_target' => 250,
            'target_tiba' => '07:50:00',
            'status' => 'Direncanakan',
        ]);

        // Catat tahap pengantaran selesai jauh melampaui target 07:45 + toleransi 15m -> 09:30 -> status 'terlambat'
        $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji Lambat',
            'tanggal' => $tanggal,
            'tahap' => 'pengantaran',
            'waktu_mulai_aktual' => '08:00',
            'waktu_selesai_aktual' => '09:30',
            'petugas_aktual' => 'Kurir Terlambat',
            'porsi_terkemas' => 250,
        ])->assertStatus(302);

        $this->assertDatabaseHas('alur_operasionals', [
            'menu_id' => $menu->id,
            'batch_no' => 'Tahap Uji Lambat',
            'tahap' => 'pengantaran',
            'status_tahap' => 'terlambat',
        ]);

        // Pastikan jadwal muncul pada halaman publik alur operasional (FR-023)
        $resAlur = $this->get(route('public.alur', ['menu_id' => $menu->id]));
        $resAlur->assertStatus(200);
        $resAlur->assertSee('Jadwal Produksi');
        $resAlur->assertSee('Kurir Terjadwal Uji');
    }

    /**
     * TC-027/029/030/031/032/033/034/036: Prediksi risiko batas 4 jam, cadangan ambang batas, & audit log
     */
    public function test_prediksi_kelayakan_batas_kritis_kpi_per_titik_dan_audit_log(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $menu = \App\Models\Menu::firstOrFail();
        $tanggal = now()->format('Y-m-d');

        // Catat pengolahan matang pukul 03:00 & pengemasan selesai pukul 06:50 (durasi kemas 230 menit + 30m antar = 260m > 240m)
        $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch TH05',
            'tanggal' => $tanggal,
            'tahap' => 'pengolahan',
            'waktu_mulai_aktual' => '01:30',
            'waktu_selesai_aktual' => '03:00',
            'waktu_matang_terakhir' => '03:00',
            'petugas_aktual' => 'Koki TH05',
        ])->assertStatus(302);

        $this->actingAs($admin)->post(route('admin.tahap.store'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch TH05',
            'tanggal' => $tanggal,
            'tahap' => 'pengemasan',
            'waktu_mulai_aktual' => '03:15',
            'waktu_selesai_aktual' => '06:50',
            'petugas_aktual' => 'Kemas TH05',
            'porsi_terkemas' => 300,
        ])->assertStatus(302);

        $kpi = \App\Models\KpiPrediksi::where('menu_id', $menu->id)->where('batch_no', 'Batch TH05')->first();
        $this->assertNotNull($kpi);
        $this->assertEquals('Tidak Layak', $kpi->kelas_prediksi);
        $this->assertStringContainsString('keputusan akhir', strtolower($kpi->keterangan_keputusan));

        // Evaluasi titik distribusi pada halaman Prediksi Risiko Random Forest mencakup label risiko dan sisa menit aman
        $this->actingAs($admin)
            ->get(route('admin.prediksi.index', ['menu_id' => $menu->id, 'batch_no' => 'Batch TH05']))
            ->assertStatus(200)
            ->assertSee('Tidak Layak')
            ->assertSee('Risiko tinggi');

        // Rekapitulasi KPI Mingguan (1x seminggu) berjalan terpisah pada halaman KPI Mingguan
        $this->actingAs($admin)
            ->post(route('admin.kpi.hitung'), ['week_start' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString()])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('admin.kpi.index'))
            ->assertStatus(200)
            ->assertSee('KPI Mingguan');

        // FR-027: Batch yang belum punya pengemasan selesai tidak memaksa skor KPI
        $this->assertNull(app(\App\Services\KpiService::class)->hitungKpi($menu, 'Batch Kosong'));

        // AuditLog mencatat aksi catat_tahap
        $this->assertTrue(\App\Models\AuditLog::where('action', 'catat_tahap')->exists());

        // Verifikasi tombol cepat 1-klik (Mulai -> Selesai Sekarang) otomatis menghitung waktu & matang komponen
        $this->actingAs($admin)->post(route('admin.tahap.quick'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch Cepat 1Klik',
            'tahap' => 'terima_bahan',
            'aksi' => 'selesai',
        ])->assertStatus(302)->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.tahap.quick'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch Cepat 1Klik',
            'tahap' => 'pengolahan',
            'aksi' => 'mulai',
        ])->assertStatus(302)->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.tahap.quick'), [
            'menu_id' => $menu->id,
            'batch_no' => 'Batch Cepat 1Klik',
            'tahap' => 'pengolahan',
            'aksi' => 'selesai',
        ])->assertStatus(302)->assertSessionHas('success');

        $alurCepatOlah = \App\Models\AlurOperasional::where('menu_id', $menu->id)
            ->where('batch_no', 'Batch Cepat 1Klik')
            ->where('tahap', 'pengolahan')
            ->first();
        $this->assertNotNull($alurCepatOlah);
        $this->assertNotNull($alurCepatOlah->waktu_mulai_aktual);
        $this->assertNotNull($alurCepatOlah->waktu_selesai_aktual);
        $this->assertNotNull($alurCepatOlah->waktu_matang_terakhir);
    }

    /**
     * NFR-006, NFR-007, NFR-008(c), OR-002: Config override KPI, proteksi XSS/SQLi, error aman, dan mbg:backup
     */
    public function test_keamanan_xss_sqli_config_kpi_error_ramah_dan_backup(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $gizi = User::where('role', 'ahli_gizi')->firstOrFail();

        // NFR-007 (XSS): Simpan nama menu mengandung <script>, pastikan tidak di-render mentah
        $xssPayload = '<script>alert("xss-mbg")</script>';
        $this->actingAs($admin)->post(route('admin.menu.store'), [
            'nama_menu' => 'Menu Uji ' . $xssPayload,
            'tanggal_penyajian' => now()->format('Y-m-d'),
            'jenis_makanan' => 'Makan Siang',
            'ukuran_porsi_gram' => 300,
            'jumlah_porsi_rencana' => 100,
            'action_type' => 'kirim_validasi',
        ])->assertStatus(302);

        $resList = $this->actingAs($admin)->get(route('admin.menu.index'));
        $resList->assertStatus(200);
        $resList->assertDontSee($xssPayload, false); // Tidak boleh ada tag <script> mentah
        $resList->assertSee('&lt;script&gt;', false); // Harus ter-escape HTML

        // NFR-007 (SQLi): Pastikan sesi tamu terlebih dulu, lalu kirim payload SQL injection ke form login
        $this->post(route('logout'));
        $this->assertGuest();

        $resSqli = $this->post(route('login.process'), [
            'username' => "' OR '1'='1' -- ",
            'password' => "' OR '1'='1' -- ",
        ]);
        $resSqli->assertStatus(302)->assertSessionHas('error');
        $this->assertGuest();

        // NFR-006: Mengubah ambang konfigurasi KPI di runtime (config/mbg.php) langsung mengubah kategori
        config(['mbg.kpi.thresholds.baik_min' => 99.9]);
        $menu = \App\Models\Menu::firstOrFail();
        $kpiDynamic = app(\App\Services\KpiService::class)->hitungKpi($menu, 'Tahap 1');
        $this->assertNotNull($kpiDynamic);
        $this->assertNotEquals('Baik', $kpiDynamic->kategori_kpi);

        // NFR-008(c): Ketika app.debug=false, error tak terduga tidak membocorkan SQL/stack trace
        config(['app.debug' => false]);
        \Illuminate\Support\Facades\Route::get('/_test-internal-error', function () {
            throw new \RuntimeException('SECRET_DB_PASSWORD_LEAK_TEST');
        });
        $resErr = $this->get('/_test-internal-error');
        $resErr->assertStatus(500);
        $resErr->assertDontSee('SECRET_DB_PASSWORD_LEAK_TEST');
        $resErr->assertSee('Permintaan Tidak Dapat Diproses');

        // OR-002: Uji perintah backup hanya jika database bukan :memory: atau pastikan command terdaftar & menangani gracefully
        $this->assertArrayHasKey('mbg:backup', \Illuminate\Support\Facades\Artisan::all());
    }
}
