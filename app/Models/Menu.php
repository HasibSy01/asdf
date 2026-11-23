<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_menu',
        'tanggal_penyajian',
        'jenis_makanan',
        'ukuran_porsi_gram',
        'jumlah_porsi_rencana',
        'status',
        'catatan_revisi',
        'is_published',
        'foto_menu',
        'kelompok_penerima_id',
    ];

    protected $casts = [
        'tanggal_penyajian' => 'date',
        'is_published' => 'boolean',
        'ukuran_porsi_gram' => 'integer',
        'jumlah_porsi_rencana' => 'integer',
    ];

    public function menuBahans()
    {
        return $this->hasMany(MenuBahan::class);
    }

    public function kelompokPenerima()
    {
        return $this->belongsTo(KelompokPenerima::class);
    }

    public function jadwalProduksis()
    {
        return $this->hasMany(JadwalProduksi::class);
    }

    public function alurOperasionals()
    {
        return $this->hasMany(AlurOperasional::class);
    }

    public function kpiPrediksis()
    {
        return $this->hasMany(KpiPrediksi::class);
    }

    /**
     * Hitung akumulasi total gizi per sajian (porsi) dari bahan penyusunnya
     */
    public function hitungGiziPerSajian(): array
    {
        $totalEnergi = 0;
        $totalProtein = 0;
        $totalLemak = 0;
        $totalKarbohidrat = 0;
        $totalSerat = 0;
        $missingData = [];

        foreach ($this->menuBahans as $mb) {
            $gizi = DataGizi::where('nama_bahan', 'LIKE', '%' . $mb->bahanBaku->nama_bahan . '%')->first();
            if (!$gizi) {
                $missingData[] = $mb->bahanBaku->nama_bahan;
                continue;
            }

            // Berat per porsi dalam gram
            $gram = $mb->berat_per_sajian_gram;
            if ($gram <= 0 && $this->jumlah_porsi_rencana > 0) {
                // Konversi dari jumlah_batch kg ke gram per porsi
                $gram = ($mb->jumlah_batch * 1000) / $this->jumlah_porsi_rencana;
            }

            $factor = $gram / ($gizi->basis_berat_gram ?: 100);
            $totalEnergi += $gizi->energi_kkal * $factor;
            $totalProtein += $gizi->protein_g * $factor;
            $totalLemak += $gizi->lemak_g * $factor;
            $totalKarbohidrat += $gizi->karbohidrat_g * $factor;
            $totalSerat += $gizi->serat_g * $factor;
        }

        return [
            'energi_kkal' => round($totalEnergi, 2),
            'protein_g' => round($totalProtein, 2),
            'lemak_g' => round($totalLemak, 2),
            'karbohidrat_g' => round($totalKarbohidrat, 2),
            'serat_g' => round($totalSerat, 2),
            'missing_data' => $missingData,
            'is_complete' => empty($missingData),
        ];
    }
}

