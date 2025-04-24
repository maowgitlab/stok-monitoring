<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $fillable = [
        'item_id',
        'tanggal',
        'stok',
        'qty',
        'tipe',
        'pengirim_penerima',
        'is_archived'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'stok' => 'decimal:2',
        'is_archived' => 'boolean'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
