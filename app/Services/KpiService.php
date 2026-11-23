<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\AlurOperasional;
use App\Models\BahanBaku;
use App\Models\KelompokPenerima;
use App\Models\KpiPrediksi;
use Carbon\Carbon;

class KpiService
{
    /**
     * Hitung skor KPI untuk menu dan batch tertentu
     */
    public function hitungKpi(Menu $menu, string $batchNo = 'Tahap 1', ?int $kelompokPenerimaId = null): ?KpiPrediksi
    {
        $menu->loadMissing('menuBahans.bahanBaku');

        $today = Carbon::today();
        $alurList = AlurOperasional::where('menu_id', $menu->id)
            ->where('batch_no', $batchNo)
            ->get()
            ->keyBy('tahap');

        // Periksa kelengkapan tahap & waktu selesai minimal sampai pengemasan (FR-027)
        if (!$alurList->has('pengemasan') || !$alurList->get('pengemasan')?->waktu_selesai_aktual) {
            return null;
        }

        $weights = config('mbg.kpi.weights');
        $scores = config('mbg.kpi.scores');
        $thresholds = config('mbg.kpi.thresholds');
        $batasAmanMenit = (int) config('mbg.operasional.batas_aman_menit', 240);

        $alurTerima = $alurList->get('terima_bahan');
        $alurOlah = $alurList->get('pengolahan');
        $alurKemas = $alurList->get('pengemasan');
        $alurDistribusi = $alurList->get('pengantaran');

        // 1. Skor Sr: Penerimaan bahan
        $skorSr = $this->skorTahap(
            $alurTerima,
            (float) ($scores['sr_tepat'] ?? 91.70),
            (float) ($scores['sr_terlambat'] ?? 65.00),
            (float) ($scores['sr_tepat'] ?? 91.70)
        );

        // 2. Skor Sx: Kedaluwarsa bahan (Cek apakah ada bahan terkait yang kedaluwarsa sebelum hari ini)
        $hasExpiredIngredient = false;
        foreach ($menu->menuBahans as $mb) {
            if ($mb->bahanBaku && $mb->bahanBaku->tanggal_kedaluwarsa && $mb->bahanBaku->tanggal_kedaluwarsa->lt($today)) {
                $hasExpiredIngredient = true;
                break;
            }
        }
        $skorSx = $hasExpiredIngredient
            ? (float) ($scores['sx_kedaluwarsa'] ?? 70.00)
            : (float) ($scores['sx_aman'] ?? 95.00);

        // 3. Skor Sp: Mulai / selesai pengolahan
        $skorSp = $this->skorTahap(
            $alurOlah,
            (float) ($scores['sp_tepat'] ?? 83.30),
            (float) ($scores['sp_terlambat'] ?? 60.00),
            (float) ($scores['sp_tepat'] ?? 83.30)
        );

        // 4. Skor Sq: Pemorsian / pengemasan
        $skorSq = $this->skorTahap(
            $alurKemas,
            (float) ($scores['sq_selesai'] ?? 75.00),
            (float) ($scores['sq_terlambat'] ?? 60.00),
            (float) ($scores['sq_belum'] ?? 50.00)
        );

        // 5. Skor Sk: Kapasitas porsi
        $porsiTarget = $menu->jumlah_porsi_rencana ?: 300;
        $porsiTerkemas = $alurKemas?->porsi_terkemas ?: 280;
        $skorSk = round(($porsiTerkemas / $porsiTarget) * 100, 2);

        // 6. Skor Sw: Waktu tiba pengantaran
        $skorSw = $this->skorTahap(
            $alurDistribusi,
            (float) ($scores['sw_tepat'] ?? 90.00),
            (float) ($scores['sw_terlambat'] ?? 65.00),
            (float) ($scores['sw_belum'] ?? 50.00)
        );

        // 7. Skor Sb: Batas waktu konsumsi (Maks 4 jam = 240 menit setelah matang sesuai config)
        $skorSb = 100.00;
        $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir;
        if ($waktuMatang && $alurDistribusi?->waktu_selesai_aktual) {
            $diffMinutes = Carbon::parse($waktuMatang)->diffInMinutes(
                Carbon::parse($alurDistribusi->waktu_selesai_aktual),
                false
            );
            if ($diffMinutes > $batasAmanMenit) {
                $skorSb = 0.00; // Melampaui batas kritis aman konsumsi
            }
        }

        // Skor Total Gabungan Berbobot dari config/mbg.php (NFR-006)
        $skorTotal = round(
            ($skorSr * (float) ($weights['sr'] ?? 0.10)) +
            ($skorSx * (float) ($weights['sx'] ?? 0.15)) +
            ($skorSp * (float) ($weights['sp'] ?? 0.10)) +
            ($skorSq * (float) ($weights['sq'] ?? 0.15)) +
            ($skorSk * (float) ($weights['sk'] ?? 0.25)) +
            ($skorSw * (float) ($weights['sw'] ?? 0.15)) +
            ($skorSb * (float) ($weights['sb'] ?? 0.10)),
            2
        );

        // Kategori KPI berdasarkan ambang config/mbg.php
        if ($skorTotal >= (float) ($thresholds['baik_min'] ?? 90.0)) {
            $kategori = 'Baik';
        } elseif ($skorTotal >= (float) ($thresholds['perlu_perbaikan_min'] ?? 75.0)) {
            $kategori = 'Perlu perbaikan';
        } else {
            $kategori = 'Prioritas perbaikan';
        }

        return KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menu->id,
                'batch_no' => $batchNo,
            ],
            [
                'tanggal' => $today,
                'kelompok_penerima_id' => $kelompokPenerimaId ?: $menu->kelompok_penerima_id,
                'skor_sr' => $skorSr,
                'skor_sx' => $skorSx,
                'skor_sp' => $skorSp,
                'skor_sq' => $skorSq,
                'skor_sk' => $skorSk,
                'skor_sw' => $skorSw,
                'skor_sb' => $skorSb,
                'skor_total' => $skorTotal,
                'kategori_kpi' => $kategori,
                'is_warning_kedaluwarsa' => $hasExpiredIngredient,
            ]
        );
    }

    /**
     * Hitung skor indikator tahap berdasarkan status ketepatan waktu (FR-026).
     */
    protected function skorTahap(?AlurOperasional $alur, float $skorTepat, float $skorTerlambat, float $skorBelum): float
    {
        if (!$alur || !$alur->waktu_selesai_aktual) {
            return $skorBelum;
        }

        return $alur->status_tahap === 'terlambat' ? $skorTerlambat : $skorTepat;
    }
}

