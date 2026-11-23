<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BahanBaku extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bahan',
        'kode_lot',
        'kategori',
        'stok_kg',
        'satuan',
        'is_utama',
        'tanggal_masuk',
        'tanggal_kedaluwarsa',
        'supplier',
    ];

    protected $casts = [
        'tanggal_masuk' => 'datetime',
        'tanggal_kedaluwarsa' => 'datetime',
        'is_utama' => 'boolean',
        'stok_kg' => 'decimal:2',
    ];

    public function menuBahans()
    {
        return $this->hasMany(MenuBahan::class);
    }

    public function getStatusKedaluwarsaAttribute(): string
    {
        $now = Carbon::now();
        if ($this->tanggal_kedaluwarsa->isPast()) {
            return 'kedaluwarsa';
        }
        if ($this->tanggal_kedaluwarsa->diffInHours($now) <= 24) {
            return 'segera_pakai';
        }
        return 'valid';
    }
}

