<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\KategoriFasilitas;
use App\Models\Fasilitas;

class PosyanduTkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $katPosyandu = KategoriFasilitas::where('nama', 'Posyandu')->first();
        $katTk = KategoriFasilitas::where('nama', 'TK / PAUD')->first();

        if (!$katPosyandu || !$katTk) {
            $this->command->error("Kategori Posyandu atau TK tidak ditemukan.");
            return;
        }

        $kecamatans = Kecamatan::whereNotNull('geom')->get();
        
        $posyanduCount = 0;
        $tkCount = 0;

        foreach ($kecamatans as $kec) {
            // Get centroid of the kecamatan
            $centroid = DB::selectOne("SELECT ST_X(ST_Centroid(ST_GeomFromGeoJSON(geom::text))) as lon, ST_Y(ST_Centroid(ST_GeomFromGeoJSON(geom::text))) as lat FROM kecamatans WHERE id = ?", [$kec->id]);
            
            if (!$centroid || !$centroid->lat || !$centroid->lon) continue;

            $baseLat = $centroid->lat;
            $baseLon = $centroid->lon;

            // Generate 3 Posyandu
            for ($i = 1; $i <= 3; $i++) {
                // random offset between -0.015 and +0.015 degrees (~1.5km)
                $latOffset = (mt_rand(-150, 150) / 10000);
                $lonOffset = (mt_rand(-150, 150) / 10000);
                
                Fasilitas::create([
                    'nama' => "Posyandu Melati " . $i . " " . ucfirst(strtolower($kec->nama)),
                    'kecamatan_id' => $kec->id,
                    'kategori_id' => $katPosyandu->id,
                    'latitude' => $baseLat + $latOffset,
                    'longitude' => $baseLon + $lonOffset,
                    'deskripsi' => "Posyandu aktif di wilayah " . ucfirst(strtolower($kec->nama)),
                ]);
                $posyanduCount++;
            }

            // Generate 2 TK/PAUD
            for ($i = 1; $i <= 2; $i++) {
                $latOffset = (mt_rand(-150, 150) / 10000);
                $lonOffset = (mt_rand(-150, 150) / 10000);
                
                $namaTk = $i === 1 ? "TK Tunas Bangsa " : "PAUD Kasih Ibu ";
                Fasilitas::create([
                    'nama' => $namaTk . ucfirst(strtolower($kec->nama)),
                    'kecamatan_id' => $kec->id,
                    'kategori_id' => $katTk->id,
                    'latitude' => $baseLat + $latOffset,
                    'longitude' => $baseLon + $lonOffset,
                    'deskripsi' => "Fasilitas pendidikan usia dini di " . ucfirst(strtolower($kec->nama)),
                ]);
                $tkCount++;
            }
        }

        $this->command->info("Berhasil membuat data dummy: {$posyanduCount} Posyandu dan {$tkCount} TK/PAUD.");
    }
}
