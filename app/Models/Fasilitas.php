<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    protected $fillable = [
        'nama', 'kategori_id', 'kecamatan_id', 
        'latitude', 'longitude', 'foto', 'deskripsi'
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriFasilitas::class, 'kategori_id');
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
