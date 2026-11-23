<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\AlurOperasional;
use App\Models\KelompokPenerima;
use App\Models\KpiPrediksi;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class PredictionService
{
    /**
     * Jalankan prediksi kelayakan konsumsi Random Forest (FR-028 s/d FR-032)
     */
    public function jalankanPrediksi(Menu $menu, string $batchNo = 'Tahap 1', ?int $kelompokPenerimaId = null): array
    {
        $today = Carbon::today();
        $alurOlah = AlurOperasional::where('menu_id', $menu->id)
            ->where('batch_no', $batchNo)
            ->where('tahap', 'pengolahan')
            ->first();

        $alurKemas = AlurOperasional::where('menu_id', $menu->id)
            ->where('batch_no', $batchNo)
            ->where('tahap', 'pengemasan')
            ->first();

        // FR-030: Jika data waktu tidak lengkap, rekomendasi belum dapat dibuat
        if (!$alurKemas || !$alurKemas->waktu_selesai_aktual) {
            return [
                'status' => 'data_tidak_lengkap',
                'message' => 'Data waktu belum lengkap untuk menjalankan prediksi. Waktu selesai kemas wajib dicatat.',
                'kelas' => null,
                'probabilitas' => null,
            ];
        }

        $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;
        if (!$waktuMatang) {
            return [
                'status' => 'data_tidak_lengkap',
                'message' => 'Waktu makanan matang belum dicatat.',
                'kelas' => null,
                'probabilitas' => null,
            ];
        }

        $kelompok = KelompokPenerima::find($kelompokPenerimaId ?: $menu->kelompok_penerima_id);
        $estimasiTempuh = $kelompok ? $kelompok->estimasi_waktu_tempuh_menit : 30;

        $payload = [
            'waktu_matang' => Carbon::parse($waktuMatang)->toIso8601String(),
            'waktu_kemas_selesai' => Carbon::parse($alurKemas->waktu_selesai_aktual)->toIso8601String(),
            'estimasi_antar_menit' => $estimasiTempuh,
            'porsi' => $alurKemas->porsi_terkemas ?: $menu->jumlah_porsi_rencana,
        ];

        // Coba panggil modul Python (batas respons 5 detik, FR-028 & SI-01)
        $pythonPath = (string) config('mbg.ml.python_bin', 'python');
        $scriptPath = (string) config('mbg.ml.script_path', base_path('scripts/random_forest_service.py'));
        $timeout = (int) config('mbg.ml.timeout_seconds', 5);
        $pythonSuccess = false;
        $resultData = null;

        try {
            $process = new Process([$pythonPath, $scriptPath, json_encode($payload)]);
            $process->setTimeout($timeout); // Maks sesuai config/mbg.php
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $json = json_decode($output, true);
                if ($json && isset($json['success']) && $json['success'] === true) {
                    $pythonSuccess = true;
                    $resultData = $json;
                }
            }
        } catch (\Throwable $e) {
            $pythonSuccess = false;
        }

        // FR-032: Jika modul Python gagal atau tidak merespons dalam 5 detik, gunakan aturan ambang batas cadangan
        if (!$pythonSuccess || !$resultData) {
            $resultData = $this->hitungCadanganAmbangBatas($waktuMatang, $alurKemas->waktu_selesai_aktual, $estimasiTempuh);
            $isFallback = true;
        } else {
            $isFallback = false;
        }

        // Simpan hasil ke KpiPrediksi
        $kpi = KpiPrediksi::updateOrCreate(
            [
                'menu_id' => $menu->id,
                'batch_no' => $batchNo,
            ],
            [
                'tanggal' => $today,
                'kelompok_penerima_id' => $kelompok?->id,
                'kelas_prediksi' => $resultData['kelas'],
                'probabilitas_risiko' => $resultData['probabilitas'],
                'keterangan_keputusan' => 'Hasil rekomendasi adalah bahan pertimbangan. Keputusan akhir kelayakan konsumsi makanan berada pada petugas yang berwenang. (FR-031)',
            ]
        );

        return [
            'status' => 'success',
            'is_fallback' => $isFallback,
            'kelas' => $resultData['kelas'],
            'probabilitas' => $resultData['probabilitas'],
            'kpi' => $kpi,
            'message' => $isFallback
                ? 'Rekomendasi dihitung menggunakan aturan ambang batas waktu cadangan (FR-032).'
                : 'Prediksi Random Forest berhasil diproses.',
            'keterangan' => 'Hasil rekomendasi merupakan bahan pertimbangan dan keputusan akhir berada pada petugas yang berwenang.',
        ];
    }

    /**
     * Evaluasi estimasi risiko per titik pengantaran untuk tabel KPI (reusable helper).
     */
    public function evaluasiRisikoTitik($waktuMatang, $waktuKemasSelesai, int $estimasiAntarMenit): array
    {
        if (!$waktuMatang || !$waktuKemasSelesai) {
            return [
                'status_risiko' => 'Menunggu data',
                'kelas' => 'Belum dinilai',
                'probabilitas' => null,
                'sisa_menit_aman' => null,
            ];
        }

        $eval = $this->hitungCadanganAmbangBatas($waktuMatang, $waktuKemasSelesai, $estimasiAntarMenit);
        $totalMenit = Carbon::parse($waktuMatang)->diffInMinutes(Carbon::parse($waktuKemasSelesai)) + $estimasiAntarMenit;
        $batasAman = (int) config('mbg.operasional.batas_aman_menit', 240);
        $sisaMenit = max(0, $batasAman - $totalMenit);

        if ($eval['kelas'] === 'Tidak Layak' || $eval['probabilitas'] >= 50) {
            $label = 'Risiko tinggi';
        } elseif ($totalMenit >= 120 || $eval['probabilitas'] >= 25) {
            $label = 'Waspada';
        } else {
            $label = 'Aman';
        }

        return [
            'status_risiko' => $label,
            'kelas' => $eval['kelas'],
            'probabilitas' => $eval['probabilitas'],
            'sisa_menit_aman' => $sisaMenit,
        ];
    }

    /**
     * Logika ambang batas waktu cadangan jika Python gagal (FR-032)
     */
    protected function hitungCadanganAmbangBatas($waktuMatang, $waktuKemasSelesai, int $estimasiAntarMenit): array
    {
        $matang = Carbon::parse($waktuMatang);
        $kemas = Carbon::parse($waktuKemasSelesai);

        $durasiKemasMenit = $matang->diffInMinutes($kemas);
        $totalWaktuMenit = $durasiKemasMenit + $estimasiAntarMenit;

        $batasAman = (int) config('mbg.operasional.batas_aman_menit', 240);
        $ambangWaspada = (int) config('mbg.operasional.ambang_waspada_menit', 200);
        $probWaspada = (float) config('mbg.ml.probabilitas_batas_waspada', 64.0);

        // Batas kritis sesuai config (default 4 jam = 240 menit)
        if ($totalWaktuMenit > $batasAman) {
            $kelas = 'Tidak Layak';
            $probabilitas = min(98.0, 75.0 + ($totalWaktuMenit - $batasAman) * 0.5);
        } elseif ($totalWaktuMenit >= $ambangWaspada) {
            $kelas = 'Tidak Layak';
            $probabilitas = $probWaspada;
        } else {
            $kelas = 'Layak';
            $probabilitas = max(15.0, round(($totalWaktuMenit / max(1, $batasAman)) * 35, 1));
        }

        return [
            'kelas' => $kelas,
            'probabilitas' => $probabilitas,
        ];
    }
}

