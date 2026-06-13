<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        User::updateOrCreate(
            ['email' => 'admin@sigap.com'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        // 2. Load and insert Kategori Fasilitas
        $kategoriPath = database_path('data/kategori_fasilitas.json');
        if (file_exists($kategoriPath)) {
            $kategoris = json_decode(file_get_contents($kategoriPath), true);
            foreach ($kategoris as $k) {
                DB::table('kategori_fasilitas')->updateOrCreate(
                    ['id' => $k['id']],
                    [
                        'nama' => $k['nama'],
                        'slug' => $k['slug'],
                        'ikon' => $k['ikon'],
                        'warna' => $k['warna'],
                        'created_at' => $k['created_at'],
                        'updated_at' => $k['updated_at'],
                    ]
                );
            }
            $this->command->info('Kategori Fasilitas seeded.');
        }

        // 3. Load and insert Kecamatans
        $kecamatansPath = database_path('data/kecamatans.json');
        if (file_exists($kecamatansPath)) {
            $kecamatans = json_decode(file_get_contents($kecamatansPath), true);
            foreach ($kecamatans as $kec) {
                // Determine geom
                $geom = $kec['geom'];
                $geomExpr = null;
                if ($geom) {
                    $geomExpr = DB::raw("ST_GeomFromGeoJSON('" . $geom . "')");
                }
                
                DB::table('kecamatans')->updateOrCreate(
                    ['id' => $kec['id']],
                    [
                        'nama' => $kec['nama'],
                        'populasi' => $kec['populasi'],
                        'luas_wilayah' => $kec['luas_wilayah'],
                        'geom' => $geomExpr
                    ]
                );
            }
            $this->command->info('Kecamatan seeded.');
        }

        // 4. Load and insert Fasilitas
        $fasilitasPath = database_path('data/fasilitas.json');
        if (file_exists($fasilitasPath)) {
            $fasilitas = json_decode(file_get_contents($fasilitasPath), true);
            foreach ($fasilitas as $f) {
                DB::table('fasilitas')->updateOrCreate(
                    ['id' => $f['id']],
                    [
                        'kategori_id' => $f['kategori_id'],
                        'kecamatan_id' => $f['kecamatan_id'],
                        'nama' => $f['nama'],
                        'deskripsi' => $f['deskripsi'],
                        'latitude' => $f['latitude'],
                        'longitude' => $f['longitude'],
                        'foto' => $f['foto'],
                        'created_at' => $f['created_at'],
                        'updated_at' => $f['updated_at'],
                    ]
                );
            }
            $this->command->info('Fasilitas seeded.');
        }
        
        // Reset PostgreSQL Sequences to prevent ID collision on insert
        DB::statement("SELECT setval('kategori_fasilitas_id_seq', (SELECT MAX(id) FROM kategori_fasilitas))");
        DB::statement("SELECT setval('kecamatans_id_seq', (SELECT MAX(id) FROM kecamatans))");
        DB::statement("SELECT setval('fasilitas_id_seq', (SELECT MAX(id) FROM fasilitas))");
    }
}
