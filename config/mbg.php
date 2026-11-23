<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi KPI & Ambang Batas Operasional MBG NutriView (NFR-006, OI-01)
    |--------------------------------------------------------------------------
    | Semua bobot, toleransi waktu, batas tahap Petunjuk Teknis BGN, serta
    | parameter integrasi Python Random Forest dapat disesuaikan di sini tanpa
    | mengubah kode inti layanan.
    */

    'kpi' => [
        // Bobot indikator KPI (jumlah ideal = 1.00)
        'weights' => [
            'sr' => (float) env('MBG_WEIGHT_SR', 0.10), // Penerimaan bahan
            'sx' => (float) env('MBG_WEIGHT_SX', 0.15), // Kedaluwarsa bahan
            'sp' => (float) env('MBG_WEIGHT_SP', 0.10), // Mulai pengolahan
            'sq' => (float) env('MBG_WEIGHT_SQ', 0.15), // Pemorsian / pengemasan
            'sk' => (float) env('MBG_WEIGHT_SK', 0.25), // Kapasitas porsi (Wk)
            'sw' => (float) env('MBG_WEIGHT_SW', 0.15), // Waktu tiba pengantaran (Wt)
            'sb' => (float) env('MBG_WEIGHT_SB', 0.10), // Batas waktu konsumsi 4 jam
        ],

        // Ambang skor default indikator saat tepat waktu / terlambat
        'scores' => [
            'sr_tepat' => 91.70,
            'sr_terlambat' => 65.00,
            'sx_aman' => 95.00,
            'sx_kedaluwarsa' => 70.00,
            'sp_tepat' => 83.30,
            'sp_terlambat' => 60.00,
            'sq_selesai' => 75.00,
            'sq_terlambat' => 60.00,
            'sq_belum' => 50.00,
            'sw_tepat' => 90.00,
            'sw_terlambat' => 65.00,
            'sw_belum' => 50.00,
        ],

        // Klasifikasi kategori KPI
        'thresholds' => [
            'baik_min' => (float) env('MBG_KPI_BAIK_MIN', 90.0),
            'perlu_perbaikan_min' => (float) env('MBG_KPI_PERBAIKAN_MIN', 75.0),
        ],
    ],

    // Parameter batas waktu kritis & toleransi (menit) sesuai Petunjuk Teknis BGN
    'operasional' => [
        'batas_aman_menit' => (int) env('MBG_BATAS_AMAN_MENIT', 240), // 4 jam setelah matang
        'ambang_waspada_menit' => (int) env('MBG_AMBANG_WASPADA_MENIT', 200),
        'toleransi_terlambat_menit' => (int) env('MBG_TOLERANSI_TERLAMBAT_MENIT', 15), // Toleransi T

        // Target selesai standar per tahap (HH:MM WITA) jika jadwal khusus tidak diisi
        'batas_tahap' => [
            'terima_bahan' => '02:00',
            'pengolahan' => '05:30',
            'pengemasan' => '06:30',
            'pengantaran' => '08:00',
        ],
    ],

    // Konfigurasi modul prediksi Python Random Forest (SI-01 & FR-032)
    'ml' => [
        'python_bin' => env('MBG_PYTHON_BIN', 'python'),
        'script_path' => base_path('scripts/random_forest_service.py'),
        'timeout_seconds' => (int) env('MBG_PYTHON_TIMEOUT', 5),
        'probabilitas_batas_waspada' => 64.0,
    ],

    // Konfigurasi pencadangan basis data (OR-002)
    'backup' => [
        'retention_days' => (int) env('MBG_BACKUP_RETENTION_DAYS', 14),
        'directory' => 'backups',
        'mysqldump_bin' => env('MBG_MYSQLDUMP_BIN', 'E:\\laragon-kipli\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe'),
    ],
];

