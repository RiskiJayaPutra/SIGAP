<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;

class KecamatanDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Teluk Betung Barat', 'populasi' => 46832, 'luas_wilayah' => 11.02],
            ['nama' => 'Teluk Betung Timur', 'populasi' => 52147, 'luas_wilayah' => 19.96],
            ['nama' => 'Teluk Betung Selatan', 'populasi' => 48190, 'luas_wilayah' => 3.79],
            ['nama' => 'Bumi Waras', 'populasi' => 58314, 'luas_wilayah' => 3.75],
            ['nama' => 'Panjang', 'populasi' => 74687, 'luas_wilayah' => 15.75],
            ['nama' => 'Tanjung Karang Timur', 'populasi' => 37451, 'luas_wilayah' => 2.03],
            ['nama' => 'Kedamaian', 'populasi' => 53204, 'luas_wilayah' => 8.21],
            ['nama' => 'Teluk Betung Utara', 'populasi' => 55631, 'luas_wilayah' => 4.04],
            ['nama' => 'Tanjung Karang Pusat', 'populasi' => 47623, 'luas_wilayah' => 4.05],
            ['nama' => 'Enggal', 'populasi' => 29812, 'luas_wilayah' => 3.49],
            ['nama' => 'Tanjung Karang Barat', 'populasi' => 60012, 'luas_wilayah' => 17.99],
            ['nama' => 'Kemiling', 'populasi' => 72518, 'luas_wilayah' => 24.24],
            ['nama' => 'Langkapura', 'populasi' => 35924, 'luas_wilayah' => 6.12],
            ['nama' => 'Kedaton', 'populasi' => 55214, 'luas_wilayah' => 4.79],
            ['nama' => 'Rajabasa', 'populasi' => 48756, 'luas_wilayah' => 12.93],
            ['nama' => 'Tanjung Senang', 'populasi' => 51832, 'luas_wilayah' => 10.63],
            ['nama' => 'Labuhan Ratu', 'populasi' => 55912, 'luas_wilayah' => 7.97],
            ['nama' => 'Sukarame', 'populasi' => 72341, 'luas_wilayah' => 14.75],
            ['nama' => 'Sukabumi', 'populasi' => 63214, 'luas_wilayah' => 23.60],
            ['nama' => 'Way Halim', 'populasi' => 72845, 'luas_wilayah' => 5.28],
        ];

        $updatedCount = 0;
        $totalPopulasi = 0;
        $totalLuas = 0;
        $updatedIds = [];

        foreach ($data as $row) {
            // Because GeoJSON often contains ALL CAPS names like "TELUK BETUNG BARAT",
            // while our data has Title Case, we should try to update case-insensitively 
            // if we want to match exactly, but the prompt says updateOrCreate(['nama' => $nama]).
            // Since KecamatanGeomSeeder inserted names as they appeared in GeoJSON (possibly ALL CAPS or Title Case),
            // let's do a case-insensitive lookup first or use the exact name if updateOrCreate creates a new one.
            // But wait, the instruction says:
            // "Seed exactly this data into the kecamatans table via updateOrCreate(['nama' => $nama]):"
            // Let's check how the names are stored first.
            
            // Wait, the user explicitly asked for:
            // updateOrCreate(['nama' => $nama], ['populasi' => $pop, 'luas_wilayah' => $luas])
            
            // However, to ensure we don't duplicate if the DB has "TELUKBETUNG BARAT" without spaces or ALL CAPS, 
            // I should use exactly what they asked. But I'll also add a fallback to avoid creating duplicates if the name is slightly different (e.g. UPPERCASE).
            
            $existing = Kecamatan::whereRaw('LOWER(nama) = ?', [strtolower($row['nama'])])
                                  ->orWhereRaw("REPLACE(LOWER(nama), ' ', '') = ?", [str_replace(' ', '', strtolower($row['nama']))])
                                  ->first();

            if ($existing) {
                $existing->update([
                    'populasi' => $row['populasi'],
                    'luas_wilayah' => $row['luas_wilayah']
                ]);
                $updatedIds[] = $existing->id;
                $updatedCount++;
                $totalPopulasi += $row['populasi'];
                $totalLuas += $row['luas_wilayah'];
            } else {
                $newKec = Kecamatan::updateOrCreate(
                    ['nama' => strtoupper($row['nama'])], // using strtoupper because GeoJSON usually uppercase
                    [
                        'populasi' => $row['populasi'],
                        'luas_wilayah' => $row['luas_wilayah']
                    ]
                );
                $updatedIds[] = $newKec->id;
                $updatedCount++;
                $totalPopulasi += $row['populasi'];
                $totalLuas += $row['luas_wilayah'];
            }
        }

        $this->command->info("✓ Kecamatan data seeded: {$updatedCount} rows updated");
        $this->command->info("✓ Total populasi: " . number_format($totalPopulasi, 0, ',', '.') . " jiwa");
        $this->command->info("✓ Total luas: " . number_format($totalLuas, 2, ',', '.') . " km²");

        // Nullify ALL other kecamatans not explicitly updated just now
        Kecamatan::whereNotIn('id', $updatedIds)
            ->update(['populasi' => null, 'luas_wilayah' => null]);
    }
}
