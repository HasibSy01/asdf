<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiPrediksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'batch_no',
        'tanggal',
        'kelompok_penerima_id',
        'skor_sr',
        'skor_sx',
        'skor_sp',
        'skor_sq',
        'skor_sk',
        'skor_sw',
        'skor_sb',
        'skor_total',
        'kategori_kpi',
        'kelas_prediksi',
        'probabilitas_risiko',
        'is_warning_kedaluwarsa',
        'keterangan_keputusan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'skor_sr' => 'decimal:2',
        'skor_sx' => 'decimal:2',
        'skor_sp' => 'decimal:2',
        'skor_sq' => 'decimal:2',
        'skor_sk' => 'decimal:2',
        'skor_sw' => 'decimal:2',
        'skor_sb' => 'decimal:2',
        'skor_total' => 'decimal:2',
        'probabilitas_risiko' => 'decimal:2',
        'is_warning_kedaluwarsa' => 'boolean',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function kelompokPenerima()
    {
        return $this->belongsTo(KelompokPenerima::class);
    }
}

