<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Dump kategori_fasilitas
$kategori = DB::table('kategori_fasilitas')->get();
file_put_contents(__DIR__ . '/database/data/kategori_fasilitas.json', json_encode($kategori, JSON_PRETTY_PRINT));

// Dump kecamatans
$kecamatans = DB::table('kecamatans')->select('id', 'nama', 'populasi', 'luas_wilayah', DB::raw('geom::text as geom'))->get();
file_put_contents(__DIR__ . '/database/data/kecamatans.json', json_encode($kecamatans, JSON_PRETTY_PRINT));

// Dump fasilitas
$fasilitas = DB::table('fasilitas')->get();
file_put_contents(__DIR__ . '/database/data/fasilitas.json', json_encode($fasilitas, JSON_PRETTY_PRINT));

echo "Database dumped successfully to JSON files!\n";
