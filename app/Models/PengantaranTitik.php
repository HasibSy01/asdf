<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengantaranTitik extends Model
{
    use HasFactory;

    protected $fillable = [
        'alur_operasional_id',
        'kelompok_penerima_id',
        'petugas_antar',
        'porsi_dikirim',
        'porsi_besar_dikirim',
        'porsi_kecil_dikirim',
        'waktu_berangkat_aktual',
        'waktu_tiba_aktual',
        'petugas_penerima',
        'status_antar',
    ];

    protected $casts = [
        'waktu_berangkat_aktual' => 'datetime',
        'waktu_tiba_aktual' => 'datetime',
        'porsi_dikirim' => 'integer',
        'porsi_besar_dikirim' => 'integer',
        'porsi_kecil_dikirim' => 'integer',
    ];

    public function alurOperasional()
    {
        return $this->belongsTo(AlurOperasional::class);
    }

    public function kelompokPenerima()
    {
        return $this->belongsTo(KelompokPenerima::class);
    }
}

