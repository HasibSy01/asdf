<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalProduksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'kelompok_penerima_id',
        'batch_no',
        'tanggal',
        'tahap',
        'waktu_mulai_rencana',
        'waktu_selesai_rencana',
        'petugas_rencana',
        'porsi_target',
        'target_tiba',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
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

