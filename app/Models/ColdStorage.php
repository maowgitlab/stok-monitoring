<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColdStorage extends Model
{
    protected $fillable = ['item_name', 'cold_storage_qty'];
}
