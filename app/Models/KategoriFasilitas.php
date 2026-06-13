<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriFasilitas extends Model
{
    protected $fillable = ['nama', 'icon', 'warna'];

    public function fasilitas()
    {
        return $this->hasMany(Fasilitas::class, 'kategori_id');
    }
}
