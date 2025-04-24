<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = ['nama_file', 'tanggal_import', 'status', 'jumlah_data'];
    
    protected $casts = [
        'tanggal_import' => 'date'
    ];
}
