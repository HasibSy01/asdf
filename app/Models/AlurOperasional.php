<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlurOperasional extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'batch_no',
        'tanggal',
        'tahap',
        'waktu_mulai_aktual',
        'waktu_selesai_aktual',
        'petugas_aktual',
        'status_tahap',
        'porsi_terkemas',
        'catatan_kendala',
        'waktu_matang_nasi',
        'waktu_matang_lauk',
        'waktu_matang_sayur',
        'waktu_matang_terakhir',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai_aktual' => 'datetime',
        'waktu_selesai_aktual' => 'datetime',
        'waktu_matang_nasi' => 'datetime',
        'waktu_matang_lauk' => 'datetime',
        'waktu_matang_sayur' => 'datetime',
        'waktu_matang_terakhir' => 'datetime',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function pengantaranTitiks()
    {
        return $this->hasMany(PengantaranTitik::class);
    }
}

