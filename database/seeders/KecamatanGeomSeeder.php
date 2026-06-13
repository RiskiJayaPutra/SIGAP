<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KecamatanGeomSeeder extends Seeder
{
    /**
     * Seed kecamatan geometry data from the static GeoJSON JS file.
     * Idempotent via updateOrCreate on nama.
     */
    public function run(): void
    {
        ini_set('memory_limit', '512M');

        $filePath = public_path('data/ADMINISTRASIKECAMATAN_AR_50K_3.js');

        if (!file_exists($filePath)) {
            $this->command->error('✗ File not found: ' . $filePath);
            return;
        }

        $this->command->info('→ Processing: ADMINISTRASIKECAMATAN_AR_50K_3.js');

        // Read and strip JS variable declaration
        $content = file_get_contents($filePath);
        $content = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
        $content = preg_replace('/;\s*$/', '', $content);

        $geojson = json_decode($content, true);

        if (!$geojson || !isset($geojson['features'])) {
            $this->command->error('✗ Failed to parse GeoJSON from file.');
            return;
        }

        $updated = 0;
        $seen = []; // Track names to handle duplicate features (e.g. PADANGCERMIN appears 3x)

        foreach ($geojson['features'] as $feature) {
            $nama = $feature['properties']['NAMOBJ'] ?? null;
            $geometry = $feature['geometry'] ?? null;

            if (!$nama || !$geometry) {
                continue;
            }

            // Skip duplicate kecamatan names — keep only the first occurrence
            if (isset($seen[$nama])) {
                continue;
            }
            $seen[$nama] = true;

            // Extract optional metadata from properties
            $luasWilayah = $feature['properties']['LUASWH'] ?? null;

            // Store geometry as JSON
            $geomJson = json_encode($geometry);

            Kecamatan::updateOrCreate(
                ['nama' => $nama],
                [
                    'geom' => $geomJson,
                    // Only update luas_wilayah if currently null and GeoJSON provides it
                    'luas_wilayah' => DB::raw("COALESCE(luas_wilayah, " . ($luasWilayah ?: 'NULL') . ")"),
                ]
            );

            $updated++;
        }

        $this->command->info("✓ {$updated} kecamatan geom updated.");

        // Rebuild GiST spatial index
        try {
            DB::statement('DROP INDEX IF EXISTS kecamatans_geom_gist_idx');
            DB::statement("
                CREATE INDEX kecamatans_geom_gist_idx
                ON kecamatans
                USING GIST (ST_GeomFromGeoJSON(geom::text))
                WHERE geom IS NOT NULL
            ");
            $this->command->info('✓ GiST index rebuilt.');
        } catch (\Exception $e) {
            $this->command->warn('⚠ GiST index rebuild failed: ' . $e->getMessage());
        }
    }
}
