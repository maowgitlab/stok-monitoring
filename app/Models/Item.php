<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['kode', 'nama', 'satuan'];

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }
    
    public function latestStock()
    {
        return $this->hasOne(StockHistory::class)->latest('tanggal');
    }
}
