<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API endpoint: mengembalikan fasilitas dari database sebagai GeoJSON
Route::get('/api/kecamatan', [\App\Http\Controllers\KecamatanController::class, 'index']);

Route::get('/api/fasilitas', function () {
    $fasilitas = \App\Models\Fasilitas::with('kategori', 'kecamatan')->get();
    
    $features = $fasilitas->map(function ($f) {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float)$f->longitude, (float)$f->latitude],
            ],
            'properties' => [
                'nama' => $f->nama,
                'kategori' => $f->kategori->nama ?? 'Lainnya',
                'kecamatan' => $f->kecamatan->nama ?? '-',
                'deskripsi' => $f->deskripsi,
                'foto' => $f->foto,
            ],
        ];
    });

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features,
    ]);
});
