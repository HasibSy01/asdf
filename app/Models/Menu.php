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
        'ukuran_porsi_besar_gram',
        'ukuran_porsi_kecil_gram',
        'jumlah_porsi_rencana',
        'porsi_besar_rencana',
        'porsi_kecil_rencana',
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
        'ukuran_porsi_besar_gram' => 'integer',
        'ukuran_porsi_kecil_gram' => 'integer',
        'jumlah_porsi_rencana' => 'integer',
        'porsi_besar_rencana' => 'integer',
        'porsi_kecil_rencana' => 'integer',
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
     * Hitung akumulasi total gizi per sajian untuk Porsi Besar ('besar') atau Porsi Kecil ('kecil')
     */
    public function hitungGiziPerSajian(string $tipePorsi = 'besar'): array
    {
        $totalEnergi = 0;
        $totalProtein = 0;
        $totalLemak = 0;
        $totalKarbohidrat = 0;
        $totalSerat = 0;
        $missingData = [];

        $gramBesarAcuan = (int) ($this->ukuran_porsi_besar_gram ?: $this->ukuran_porsi_gram ?: 450);
        $gramKecilAcuan = (int) ($this->ukuran_porsi_kecil_gram ?: max(150, round($gramBesarAcuan * 0.68)));
        $rasioKecil = $gramBesarAcuan > 0 ? ($gramKecilAcuan / $gramBesarAcuan) : 0.68;

        foreach ($this->menuBahans as $mb) {
            if (!$mb->bahanBaku) {
                continue;
            }
            $gizi = DataGizi::where('nama_bahan', 'LIKE', '%' . $mb->bahanBaku->nama_bahan . '%')->first();
            if (!$gizi) {
                $missingData[] = $mb->bahanBaku->nama_bahan;
                continue;
            }

            $gramBesar = (float) ($mb->berat_porsi_besar_gram > 0 ? $mb->berat_porsi_besar_gram : $mb->berat_per_sajian_gram);
            if ($gramBesar <= 0 && $this->jumlah_porsi_rencana > 0) {
                $gramBesar = ($mb->jumlah_batch * 1000) / $this->jumlah_porsi_rencana;
            }

            if ($tipePorsi === 'kecil') {
                $gram = (float) ($mb->berat_porsi_kecil_gram > 0 ? $mb->berat_porsi_kecil_gram : round($gramBesar * $rasioKecil, 1));
            } else {
                $gram = $gramBesar;
            }

            $factor = $gram / ($gizi->basis_berat_gram ?: 100);
            $totalEnergi += $gizi->energi_kkal * $factor;
            $totalProtein += $gizi->protein_g * $factor;
            $totalLemak += $gizi->lemak_g * $factor;
            $totalKarbohidrat += $gizi->karbohidrat_g * $factor;
            $totalSerat += $gizi->serat_g * $factor;
        }

        return [
            'tipe_porsi' => $tipePorsi,
            'ukuran_gram' => $tipePorsi === 'kecil' ? $gramKecilAcuan : $gramBesarAcuan,
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

