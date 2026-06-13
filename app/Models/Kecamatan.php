<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $fillable = ['nama', 'populasi', 'luas_wilayah', 'geom'];

    public function fasilitas()
    {
        return $this->hasMany(Fasilitas::class);
    }
}
