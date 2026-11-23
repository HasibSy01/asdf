<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuBahan extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'bahan_baku_id',
        'jumlah_batch',
        'satuan',
        'komponen',
        'berat_per_sajian_gram',
    ];

    protected $casts = [
        'jumlah_batch' => 'decimal:2',
        'berat_per_sajian_gram' => 'decimal:2',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }
}

