<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataGizi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bahan',
        'basis_berat_gram',
        'energi_kkal',
        'protein_g',
        'lemak_g',
        'karbohidrat_g',
        'serat_g',
        'vitamin_mineral',
        'sumber_catatan',
    ];

    protected $casts = [
        'basis_berat_gram' => 'decimal:2',
        'energi_kkal' => 'decimal:2',
        'protein_g' => 'decimal:2',
        'lemak_g' => 'decimal:2',
        'karbohidrat_g' => 'decimal:2',
        'serat_g' => 'decimal:2',
    ];
}

