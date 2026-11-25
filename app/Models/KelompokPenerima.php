<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelompokPenerima extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kelompok',
        'kategori',
        'jumlah_penerima',
        'penerima_porsi_besar',
        'penerima_porsi_kecil',
        'porsi_terkemas',
        'terkemas_porsi_besar',
        'terkemas_porsi_kecil',
        'titik_antar',
        'estimasi_waktu_tempuh_menit',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function jadwalProduksis()
    {
        return $this->hasMany(JadwalProduksi::class);
    }

    public function pengantaranTitiks()
    {
        return $this->hasMany(PengantaranTitik::class);
    }
}

