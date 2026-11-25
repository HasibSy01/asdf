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

        $tanggalRecord = $alurKemas?->tanggal ?? $menu->tanggal_penyajian ?? $today;

        return KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menu->id,
                'batch_no' => $batchNo,
            ],
            [
                'tanggal' => $tanggalRecord,
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
     * Hitung / ambil rekapitulasi KPI Mingguan (evaluasi cukup 1x seminggu: Senin s/d Minggu)
     */
    public function hitungRekapMingguan(?string $weekStartStr = null, bool $recalculateBatches = false): array
    {
        $startOfWeek = $weekStartStr
            ? Carbon::parse($weekStartStr)->startOfWeek(Carbon::MONDAY)
            : Carbon::today()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $startDate = $startOfWeek->toDateString();
        $endDate = $endOfWeek->toDateString();

        if ($recalculateBatches) {
            $alurPairs = AlurOperasional::where('tahap', 'pengemasan')
                ->whereNotNull('waktu_selesai_aktual')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->orWhereHas('menu', fn ($mq) => $mq->whereBetween('tanggal_penyajian', [$startDate, $endDate]));
                })
                ->get(['menu_id', 'batch_no'])
                ->unique(fn ($item) => $item->menu_id . '|' . $item->batch_no);

            foreach ($alurPairs as $pair) {
                $m = Menu::with('menuBahans.bahanBaku')->find($pair->menu_id);
                if ($m) {
                    $this->hitungKpi($m, $pair->batch_no);
                }
            }
        }

        $batchRecords = KpiPrediksi::with(['menu', 'kelompokPenerima'])
            ->whereNotNull('skor_total')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate])
                  ->orWhereHas('menu', fn ($mq) => $mq->whereBetween('tanggal_penyajian', [$startDate, $endDate]));
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $thresholds = config('mbg.kpi.thresholds');
        $count = $batchRecords->count();

        if ($count > 0) {
            $avgSr = round((float) $batchRecords->avg('skor_sr'), 2);
            $avgSx = round((float) $batchRecords->avg('skor_sx'), 2);
            $avgSp = round((float) $batchRecords->avg('skor_sp'), 2);
            $avgSq = round((float) $batchRecords->avg('skor_sq'), 2);
            $avgSk = round((float) $batchRecords->avg('skor_sk'), 2);
            $avgSw = round((float) $batchRecords->avg('skor_sw'), 2);
            $avgSb = round((float) $batchRecords->avg('skor_sb'), 2);
            $avgTotal = round((float) $batchRecords->avg('skor_total'), 2);

            if ($avgTotal >= (float) ($thresholds['baik_min'] ?? 90.0)) {
                $kategoriMingguan = 'Baik';
            } elseif ($avgTotal >= (float) ($thresholds['perlu_perbaikan_min'] ?? 75.0)) {
                $kategoriMingguan = 'Perlu perbaikan';
            } else {
                $kategoriMingguan = 'Prioritas perbaikan';
            }

            $warningKedaluwarsa = $batchRecords->contains(fn ($r) => (bool) $r->is_warning_kedaluwarsa || ((float) $r->skor_sx < 100));
            $terakhirDihitung = $batchRecords->max('updated_at');
        } else {
            $avgSr = $avgSx = $avgSp = $avgSq = $avgSk = $avgSw = $avgSb = $avgTotal = null;
            $kategoriMingguan = null;
            $warningKedaluwarsa = false;
            $terakhirDihitung = null;
        }

        return [
            'week_start' => $startDate,
            'week_end' => $endDate,
            'label_minggu' => $startOfWeek->translatedFormat('d M Y') . ' – ' . $endOfWeek->translatedFormat('d M Y'),
            'jumlah_batch' => $count,
            'skor_sr' => $avgSr,
            'skor_sx' => $avgSx,
            'skor_sp' => $avgSp,
            'skor_sq' => $avgSq,
            'skor_sk' => $avgSk,
            'skor_sw' => $avgSw,
            'skor_sb' => $avgSb,
            'skor_total' => $avgTotal,
            'kategori_kpi' => $kategoriMingguan,
            'is_warning_kedaluwarsa' => $warningKedaluwarsa,
            'terakhir_dihitung' => $terakhirDihitung,
            'batch_records' => $batchRecords,
        ];
    }

    /**
     * Daftar opsi minggu evaluasi (6 minggu terakhir + minggu yang memiliki data)
     */
    public function daftarMingguEvaluasi(): array
    {
        $weeks = [];
        $currentMonday = Carbon::today()->startOfWeek(Carbon::MONDAY);

        for ($i = 0; $i < 6; $i++) {
            $mon = $currentMonday->copy()->subWeeks($i);
            $sun = $mon->copy()->endOfWeek(Carbon::SUNDAY);
            $key = $mon->toDateString();
            $weeks[$key] = [
                'week_start' => $key,
                'label' => ($i === 0 ? 'Minggu Ini · ' : '') . $mon->translatedFormat('d M') . ' – ' . $sun->translatedFormat('d M Y'),
            ];
        }

        foreach (KpiPrediksi::whereNotNull('skor_total')->pluck('tanggal') as $tgl) {
            if (!$tgl) continue;
            $mon = Carbon::parse($tgl)->startOfWeek(Carbon::MONDAY);
            $sun = $mon->copy()->endOfWeek(Carbon::SUNDAY);
            $key = $mon->toDateString();
            if (!isset($weeks[$key])) {
                $weeks[$key] = [
                    'week_start' => $key,
                    'label' => $mon->translatedFormat('d M') . ' – ' . $sun->translatedFormat('d M Y'),
                ];
            }
        }

        krsort($weeks);
        return array_values($weeks);
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

