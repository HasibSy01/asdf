<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BahanBaku;
use App\Models\DataGizi;
use App\Models\KelompokPenerima;
use App\Models\Menu;
use App\Models\MenuBahan;
use App\Models\JadwalProduksi;
use App\Models\AlurOperasional;
use App\Models\PengantaranTitik;
use App\Models\KpiPrediksi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Pengguna (Sesuai SRS Lampiran C.2)
        $admin = User::firstOrCreate(
            ['username' => 'admin_uji'],
            [
                'name' => 'Admin SPPG Ujung Lare',
                'email' => 'admin@mbg-nutriview.id',
                'password' => Hash::make('Admin#Uji2026'),
                'role' => 'admin',
            ]
        );

        $ahliGizi = User::firstOrCreate(
            ['username' => 'gizi_uji'],
            [
                'name' => 'Ahli Gizi SPPG Parepare',
                'email' => 'gizi@mbg-nutriview.id',
                'password' => Hash::make('Gizi#Uji2026'),
                'role' => 'ahli_gizi',
            ]
        );

        // Akun alternatif cepat
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@sppg.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['username' => 'gizi'],
            [
                'name' => 'Nutrisionis SPPG',
                'email' => 'gizi@sppg.id',
                'password' => Hash::make('gizi123'),
                'role' => 'ahli_gizi',
            ]
        );

        // 2. Data Gizi Baku per 100g (Sesuai SRS Lampiran C.3)
        $dataGizi = [
            [
                'nama_bahan' => 'Beras putih',
                'basis_berat_gram' => 100,
                'energi_kkal' => 360.00,
                'protein_g' => 6.80,
                'lemak_g' => 0.70,
                'karbohidrat_g' => 78.90,
                'serat_g' => 0.40,
                'vitamin_mineral' => 'Fe 1.8 mg, Ca 10 mg',
                'sumber_catatan' => 'Tabel Komposisi Pangan Indonesia (TKPI)',
            ],
            [
                'nama_bahan' => 'Daging ayam',
                'basis_berat_gram' => 100,
                'energi_kkal' => 165.00,
                'protein_g' => 31.00,
                'lemak_g' => 3.60,
                'karbohidrat_g' => 0.00,
                'serat_g' => 0.00,
                'vitamin_mineral' => 'Fe 1.5 mg, Vit A 80 µg',
                'sumber_catatan' => 'TKPI',
            ],
            [
                'nama_bahan' => 'Telur ayam',
                'basis_berat_gram' => 100,
                'energi_kkal' => 154.00,
                'protein_g' => 12.40,
                'lemak_g' => 10.80,
                'karbohidrat_g' => 0.70,
                'serat_g' => 0.00,
                'vitamin_mineral' => 'Vit A 140 µg, Fe 2.7 mg, Ca 54 mg',
                'sumber_catatan' => 'TKPI',
            ],
            [
                'nama_bahan' => 'Tempe kedelai',
                'basis_berat_gram' => 100,
                'energi_kkal' => 201.00,
                'protein_g' => 20.80,
                'lemak_g' => 8.80,
                'karbohidrat_g' => 13.50,
                'serat_g' => 1.40,
                'vitamin_mineral' => 'Fe 4.0 mg, Ca 155 mg',
                'sumber_catatan' => 'TKPI',
            ],
            [
                'nama_bahan' => 'Bayam',
                'basis_berat_gram' => 100,
                'energi_kkal' => 36.00,
                'protein_g' => 3.50,
                'lemak_g' => 0.50,
                'karbohidrat_g' => 6.50,
                'serat_g' => 2.00,
                'vitamin_mineral' => 'Vit A 250 µg, Fe 3 mg, Ca 120 mg',
                'sumber_catatan' => 'TKPI',
            ],
            [
                'nama_bahan' => 'Pisang',
                'basis_berat_gram' => 100,
                'energi_kkal' => 99.00,
                'protein_g' => 1.20,
                'lemak_g' => 0.20,
                'karbohidrat_g' => 25.80,
                'serat_g' => 2.60,
                'vitamin_mineral' => 'Vit C 9 mg, Kalium 358 mg',
                'sumber_catatan' => 'TKPI',
            ],
            [
                'nama_bahan' => 'Ikan tongkol',
                'basis_berat_gram' => 100,
                'energi_kkal' => 117.00,
                'protein_g' => 23.20,
                'lemak_g' => 2.10,
                'karbohidrat_g' => 0.00,
                'serat_g' => 0.00,
                'vitamin_mineral' => 'Fe 1.0 mg, Ca 20 mg',
                'sumber_catatan' => 'TKPI',
            ],
        ];

        foreach ($dataGizi as $dg) {
            DataGizi::updateOrCreate(['nama_bahan' => $dg['nama_bahan']], $dg);
        }

        // 3. Kelompok Penerima (Agregat Porsi Besar & Porsi Kecil)
        $sdA = KelompokPenerima::updateOrCreate(
            ['nama_kelompok' => 'SD A / Gerbang sekolah'],
            [
                'kategori' => 'Siswa',
                'jumlah_penerima' => 100,
                'penerima_porsi_besar' => 60,
                'penerima_porsi_kecil' => 40,
                'porsi_terkemas' => 100,
                'terkemas_porsi_besar' => 60,
                'terkemas_porsi_kecil' => 40,
                'titik_antar' => 'Gerbang SD Negeri A, Kota Parepare',
                'estimasi_waktu_tempuh_menit' => 15,
            ]
        );

        $sdB = KelompokPenerima::updateOrCreate(
            ['nama_kelompok' => 'SD B / Halaman sekolah'],
            [
                'kategori' => 'Siswa',
                'jumlah_penerima' => 200,
                'penerima_porsi_besar' => 130,
                'penerima_porsi_kecil' => 70,
                'porsi_terkemas' => 180,
                'terkemas_porsi_besar' => 120,
                'terkemas_porsi_kecil' => 60,
                'titik_antar' => 'Halaman SD Negeri B, Kota Parepare',
                'estimasi_waktu_tempuh_menit' => 30,
            ]
        );

        // 4. Bahan Baku di Gudang Dapur
        $bahanBakuData = [
            [
                'nama_bahan' => 'Beras putih',
                'kode_lot' => 'R-01',
                'kategori' => 'nasi',
                'stok_kg' => 50.00,
                'satuan' => 'kg',
                'is_utama' => true,
                'tanggal_masuk' => Carbon::now()->subDays(2)->setTime(15, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addMonths(6),
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Daging ayam',
                'kode_lot' => 'A-01',
                'kategori' => 'lauk',
                'stok_kg' => 24.00,
                'satuan' => 'kg',
                'is_utama' => true,
                'tanggal_masuk' => Carbon::now()->subDays(1)->setTime(16, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addDays(2)->setTime(0, 0),
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Tempe kedelai',
                'kode_lot' => 'T-01',
                'kategori' => 'lauk',
                'stok_kg' => 10.00,
                'satuan' => 'kg',
                'is_utama' => false,
                'tanggal_masuk' => Carbon::now()->subDays(1)->setTime(16, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addDays(2)->setTime(12, 0),
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Bayam',
                'kode_lot' => 'B-01',
                'kategori' => 'sayur',
                'stok_kg' => 9.00,
                'satuan' => 'kg',
                'is_utama' => false,
                'tanggal_masuk' => Carbon::now()->setTime(1, 30),
                'tanggal_kedaluwarsa' => Carbon::now()->setTime(12, 0),
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Bayam',
                'kode_lot' => 'B-02',
                'kategori' => 'sayur',
                'stok_kg' => 1.00,
                'satuan' => 'kg',
                'is_utama' => false,
                'tanggal_masuk' => Carbon::now()->subDays(1)->setTime(12, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->subHours(2), // Sudah kedaluwarsa untuk tes peringatan
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Pisang',
                'kode_lot' => 'P-01',
                'kategori' => 'buah',
                'stok_kg' => 30.00,
                'satuan' => 'kg',
                'is_utama' => false,
                'tanggal_masuk' => Carbon::now()->subDays(1)->setTime(10, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addDays(4),
                'supplier' => 'UD Sumber Pangan',
            ],
            [
                'nama_bahan' => 'Ikan tongkol',
                'kode_lot' => 'I-01',
                'kategori' => 'lauk',
                'stok_kg' => 18.00,
                'satuan' => 'kg',
                'is_utama' => true,
                'tanggal_masuk' => Carbon::now()->setTime(2, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addDays(3),
                'supplier' => 'TPI Parepare',
            ],
            [
                'nama_bahan' => 'Telur ayam',
                'kode_lot' => 'E-01',
                'kategori' => 'lauk',
                'stok_kg' => 15.00,
                'satuan' => 'kg',
                'is_utama' => true,
                'tanggal_masuk' => Carbon::now()->subDays(1)->setTime(9, 0),
                'tanggal_kedaluwarsa' => Carbon::now()->addDays(7),
                'supplier' => 'Peternakan Sidrap',
            ],
        ];

        $bbCreated = [];
        foreach ($bahanBakuData as $bb) {
            $created = BahanBaku::updateOrCreate(['kode_lot' => $bb['kode_lot']], $bb);
            $bbCreated[$bb['kode_lot']] = $created;
        }

        // 5. Data Menu
        $menuUtama = Menu::updateOrCreate(
            ['nama_menu' => 'Paket Nasi Ayam Semur & Tempe'],
            [
                'tanggal_penyajian' => Carbon::today(),
                'jenis_makanan' => 'Makanan lengkap',
                'ukuran_porsi_gram' => 450,
                'ukuran_porsi_besar_gram' => 450,
                'ukuran_porsi_kecil_gram' => 300,
                'jumlah_porsi_rencana' => 300,
                'porsi_besar_rencana' => 190,
                'porsi_kecil_rencana' => 110,
                'status' => 'terpublikasi',
                'is_published' => true,
                'kelompok_penerima_id' => $sdA->id,
            ]
        );

        // Komposisi bahan menu utama (Porsi Besar & Porsi Kecil)
        $komposisi = [
            ['kode_lot' => 'R-01', 'jumlah_batch' => 24.0, 'satuan' => 'kg', 'komponen' => 'Nasi', 'berat_per_sajian_gram' => 80.0, 'kecil' => 55.0],
            ['kode_lot' => 'A-01', 'jumlah_batch' => 15.0, 'satuan' => 'kg', 'komponen' => 'Lauk utama', 'berat_per_sajian_gram' => 50.0, 'kecil' => 35.0],
            ['kode_lot' => 'T-01', 'jumlah_batch' => 12.0, 'satuan' => 'kg', 'komponen' => 'Lauk nabati', 'berat_per_sajian_gram' => 40.0, 'kecil' => 30.0],
            ['kode_lot' => 'B-01', 'jumlah_batch' => 15.0, 'satuan' => 'kg', 'komponen' => 'Sayur', 'berat_per_sajian_gram' => 50.0, 'kecil' => 35.0],
            ['kode_lot' => 'P-01', 'jumlah_batch' => 18.0, 'satuan' => 'kg', 'komponen' => 'Buah', 'berat_per_sajian_gram' => 60.0, 'kecil' => 45.0],
        ];

        foreach ($komposisi as $komp) {
            if (isset($bbCreated[$komp['kode_lot']])) {
                MenuBahan::updateOrCreate(
                    [
                        'menu_id' => $menuUtama->id,
                        'bahan_baku_id' => $bbCreated[$komp['kode_lot']]->id,
                    ],
                    [
                        'jumlah_batch' => $komp['jumlah_batch'],
                        'satuan' => $komp['satuan'],
                        'komponen' => $komp['komponen'],
                        'berat_per_sajian_gram' => $komp['berat_per_sajian_gram'],
                        'berat_porsi_besar_gram' => $komp['berat_per_sajian_gram'],
                        'berat_porsi_kecil_gram' => $komp['kecil'],
                    ]
                );
            }
        }

        // Menu 2: Menunggu Validasi Ahli Gizi (Besok)
        $menuKedua = Menu::updateOrCreate(
            ['nama_menu' => 'Paket Ikan Tongkol & Sayur Bening'],
            [
                'tanggal_penyajian' => Carbon::tomorrow(),
                'jenis_makanan' => 'Makanan lengkap',
                'ukuran_porsi_gram' => 450,
                'jumlah_porsi_rencana' => 300,
                'status' => 'menunggu_validasi',
                'is_published' => false,
                'kelompok_penerima_id' => $sdB->id,
            ]
        );

        foreach ([
            ['kode_lot' => 'R-01', 'jumlah_batch' => 24.0, 'satuan' => 'kg', 'komponen' => 'Nasi', 'berat_per_sajian_gram' => 80.0],
            ['kode_lot' => 'I-01', 'jumlah_batch' => 18.0, 'satuan' => 'kg', 'komponen' => 'Lauk utama', 'berat_per_sajian_gram' => 60.0],
            ['kode_lot' => 'B-01', 'jumlah_batch' => 15.0, 'satuan' => 'kg', 'komponen' => 'Sayur', 'berat_per_sajian_gram' => 50.0],
            ['kode_lot' => 'P-01', 'jumlah_batch' => 18.0, 'satuan' => 'kg', 'komponen' => 'Buah', 'berat_per_sajian_gram' => 60.0],
        ] as $komp) {
            if (isset($bbCreated[$komp['kode_lot']])) {
                MenuBahan::updateOrCreate(
                    [
                        'menu_id' => $menuKedua->id,
                        'bahan_baku_id' => $bbCreated[$komp['kode_lot']]->id,
                    ],
                    [
                        'jumlah_batch' => $komp['jumlah_batch'],
                        'satuan' => $komp['satuan'],
                        'komponen' => $komp['komponen'],
                        'berat_per_sajian_gram' => $komp['berat_per_sajian_gram'],
                    ]
                );
            }
        }

        // Menu 3: Tervalidasi (siap dipublikasikan Ahli Gizi untuk tes FR-012/014)
        $menuKetiga = Menu::updateOrCreate(
            ['nama_menu' => 'Paket Nasi Telur Balado & Tempe'],
            [
                'tanggal_penyajian' => Carbon::today()->addDays(2),
                'jenis_makanan' => 'Makanan lengkap',
                'ukuran_porsi_gram' => 450,
                'jumlah_porsi_rencana' => 300,
                'status' => 'tervalidasi',
                'is_published' => false,
                'kelompok_penerima_id' => $sdA->id,
            ]
        );

        foreach ([
            ['kode_lot' => 'R-01', 'jumlah_batch' => 24.0, 'satuan' => 'kg', 'komponen' => 'Nasi', 'berat_per_sajian_gram' => 80.0],
            ['kode_lot' => 'E-01', 'jumlah_batch' => 15.0, 'satuan' => 'kg', 'komponen' => 'Lauk utama', 'berat_per_sajian_gram' => 50.0],
            ['kode_lot' => 'T-01', 'jumlah_batch' => 12.0, 'satuan' => 'kg', 'komponen' => 'Lauk nabati', 'berat_per_sajian_gram' => 40.0],
            ['kode_lot' => 'B-01', 'jumlah_batch' => 15.0, 'satuan' => 'kg', 'komponen' => 'Sayur', 'berat_per_sajian_gram' => 50.0],
            ['kode_lot' => 'P-01', 'jumlah_batch' => 18.0, 'satuan' => 'kg', 'komponen' => 'Buah', 'berat_per_sajian_gram' => 60.0],
        ] as $komp) {
            if (isset($bbCreated[$komp['kode_lot']])) {
                MenuBahan::updateOrCreate(
                    [
                        'menu_id' => $menuKetiga->id,
                        'bahan_baku_id' => $bbCreated[$komp['kode_lot']]->id,
                    ],
                    [
                        'jumlah_batch' => $komp['jumlah_batch'],
                        'satuan' => $komp['satuan'],
                        'komponen' => $komp['komponen'],
                        'berat_per_sajian_gram' => $komp['berat_per_sajian_gram'],
                    ]
                );
            }
        }

        // 6. Jadwal Produksi
        $jadwals = [
            [
                'menu_id' => $menuUtama->id,
                'kelompok_penerima_id' => $sdA->id,
                'batch_no' => 'Tahap 1',
                'tanggal' => Carbon::today(),
                'tahap' => 'Olah · Nasi',
                'waktu_mulai_rencana' => '02:15:00',
                'waktu_selesai_rencana' => '05:30:00',
                'petugas_rencana' => 'Tim Olah Nasi',
                'porsi_target' => 300,
                'target_tiba' => '07:45:00',
                'status' => 'Selesai',
            ],
            [
                'menu_id' => $menuUtama->id,
                'kelompok_penerima_id' => $sdA->id,
                'batch_no' => 'Tahap 1',
                'tanggal' => Carbon::today(),
                'tahap' => 'Olah · Lauk ayam & tempe',
                'waktu_mulai_rencana' => '03:00:00',
                'waktu_selesai_rencana' => '05:30:00',
                'petugas_rencana' => 'Tim Olah Lauk',
                'porsi_target' => 300,
                'target_tiba' => '07:45:00',
                'status' => 'Selesai',
            ],
            [
                'menu_id' => $menuUtama->id,
                'kelompok_penerima_id' => $sdA->id,
                'batch_no' => 'Tahap 1',
                'tanggal' => Carbon::today(),
                'tahap' => 'Pemorsian / pengemasan',
                'waktu_mulai_rencana' => '05:45:00',
                'waktu_selesai_rencana' => '06:30:00',
                'petugas_rencana' => 'Tim Kemas',
                'porsi_target' => 300,
                'target_tiba' => '07:45:00',
                'status' => 'Selesai',
            ],
            [
                'menu_id' => $menuUtama->id,
                'kelompok_penerima_id' => $sdA->id,
                'batch_no' => 'Tahap 1',
                'tanggal' => Carbon::today(),
                'tahap' => 'Distribusi · dari dapur',
                'waktu_mulai_rencana' => '07:30:00',
                'waktu_selesai_rencana' => '08:00:00',
                'petugas_rencana' => 'Dedi (Armada 1)',
                'porsi_target' => 300,
                'target_tiba' => '08:00:00',
                'status' => 'Direncanakan',
            ],
        ];

        foreach ($jadwals as $jdw) {
            JadwalProduksi::updateOrCreate(
                [
                    'menu_id' => $jdw['menu_id'],
                    'batch_no' => $jdw['batch_no'],
                    'tahap' => $jdw['tahap'],
                ],
                $jdw
            );
        }

        // 7. Alur Operasional Dapur Aktual
        $today = Carbon::today();
        AlurOperasional::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 1',
                'tahap' => 'terima_bahan',
            ],
            [
                'tanggal' => $today,
                'waktu_mulai_aktual' => $today->copy()->setTime(1, 30),
                'waktu_selesai_aktual' => $today->copy()->setTime(2, 0),
                'petugas_aktual' => 'Rudi · terima bahan',
                'status_tahap' => 'selesai',
                'porsi_terkemas' => 300,
                'catatan_kendala' => 'Bahan diterima & diperiksa lengkap; lot B-02 dipisahkan.',
            ]
        );

        $alurOlah = AlurOperasional::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 1',
                'tahap' => 'pengolahan',
            ],
            [
                'tanggal' => $today,
                'waktu_mulai_aktual' => $today->copy()->setTime(2, 15),
                'waktu_selesai_aktual' => $today->copy()->setTime(5, 30),
                'petugas_aktual' => 'Budi · olah',
                'status_tahap' => 'selesai',
                'porsi_terkemas' => 300,
                'waktu_matang_nasi' => $today->copy()->setTime(5, 30),
                'waktu_matang_lauk' => $today->copy()->setTime(5, 30),
                'waktu_matang_sayur' => $today->copy()->setTime(5, 30),
                'waktu_matang_terakhir' => $today->copy()->setTime(5, 30),
            ]
        );

        $alurKemas = AlurOperasional::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 1',
                'tahap' => 'pengemasan',
            ],
            [
                'tanggal' => $today,
                'waktu_mulai_aktual' => $today->copy()->setTime(5, 45),
                'waktu_selesai_aktual' => $today->copy()->setTime(6, 30),
                'petugas_aktual' => 'Sari · kemas',
                'status_tahap' => 'selesai',
                'porsi_terkemas' => 280,
                'catatan_kendala' => 'Terkemas 280 porsi. SD B masih kurang 20 porsi; perlu tindak lanjut.',
                'waktu_matang_terakhir' => $today->copy()->setTime(5, 30),
            ]
        );

        $alurDistribusi = AlurOperasional::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 1',
                'tahap' => 'pengantaran',
            ],
            [
                'tanggal' => $today,
                'waktu_mulai_aktual' => null,
                'waktu_selesai_aktual' => null,
                'petugas_aktual' => 'Dedi · antar',
                'status_tahap' => 'belum_mulai',
                'porsi_terkemas' => 280,
                'waktu_matang_terakhir' => $today->copy()->setTime(5, 30),
            ]
        );

        // Titik pengantaran
        PengantaranTitik::updateOrCreate(
            [
                'alur_operasional_id' => $alurDistribusi->id,
                'kelompok_penerima_id' => $sdA->id,
            ],
            [
                'petugas_antar' => 'Dedi',
                'porsi_dikirim' => 100,
                'status_antar' => 'Belum distribusi',
            ]
        );

        PengantaranTitik::updateOrCreate(
            [
                'alur_operasional_id' => $alurDistribusi->id,
                'kelompok_penerima_id' => $sdB->id,
            ],
            [
                'petugas_antar' => 'Dedi',
                'porsi_dikirim' => 180,
                'status_antar' => 'Belum distribusi',
            ]
        );

        // 8. KPI & Prediksi Risiko
        KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 1',
            ],
            [
                'tanggal' => $today,
                'kelompok_penerima_id' => $sdA->id,
                'skor_sr' => 91.70,
                'skor_sx' => 95.00,
                'skor_sp' => 83.30,
                'skor_sq' => 50.00,
                'skor_sk' => 93.30,
                'skor_sw' => 50.00,
                'skor_sb' => 100.00,
                'skor_total' => 83.00,
                'kategori_kpi' => 'Perlu perbaikan',
                'kelas_prediksi' => 'Layak',
                'probabilitas_risiko' => 18.00,
                'is_warning_kedaluwarsa' => true,
                'keterangan_keputusan' => 'Hasil rekomendasi adalah bahan pertimbangan. Keputusan akhir kelayakan konsumsi makanan berada pada petugas yang berwenang.',
            ]
        );

        KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 2',
            ],
            [
                'tanggal' => $today->copy()->subDay(),
                'kelompok_penerima_id' => $sdA->id,
                'skor_sr' => 95.00,
                'skor_sx' => 100.00,
                'skor_sp' => 92.00,
                'skor_sq' => 91.00,
                'skor_sk' => 100.00,
                'skor_sw' => 96.00,
                'skor_sb' => 100.00,
                'skor_total' => 96.40,
                'kategori_kpi' => 'Baik',
                'kelas_prediksi' => 'Layak',
                'probabilitas_risiko' => 12.00,
                'is_warning_kedaluwarsa' => false,
                'keterangan_keputusan' => 'Seluruh tahapan selesai sesuai standar batas konsumsi aman.',
            ]
        );

        KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menuUtama->id,
                'batch_no' => 'Tahap 3',
            ],
            [
                'tanggal' => $today->copy()->subDays(2),
                'kelompok_penerima_id' => $sdB->id,
                'skor_sr' => 88.00,
                'skor_sx' => 85.00,
                'skor_sp' => 78.00,
                'skor_sq' => 70.00,
                'skor_sk' => 86.00,
                'skor_sw' => 65.00,
                'skor_sb' => 100.00,
                'skor_total' => 81.20,
                'kategori_kpi' => 'Perlu perbaikan',
                'kelas_prediksi' => 'Layak',
                'probabilitas_risiko' => 28.00,
                'is_warning_kedaluwarsa' => false,
                'keterangan_keputusan' => 'Perlu percepatan pemorsian pada batch sore agar estimasi pengantaran lebih optimal.',
            ]
        );
    }
}
