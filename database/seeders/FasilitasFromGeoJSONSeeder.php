<?php

namespace Database\Seeders;

use App\Models\KategoriFasilitas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FasilitasFromGeoJSONSeeder extends Seeder
{
    /**
     * Mapping of GeoJSON JS filenames to their category names.
     */
    private const FILE_CATEGORY_MAP = [
        'PENDIDIKAN_PT_50K_5.js'    => 'Pendidikan',
        'RUMAHSAKIT_PT_50K_9.js'    => 'Rumah Sakit',
        'SPBU_PT_50K_7.js'          => 'SPBU',
        'SARANAIBADAH_PT_50K_6.js'  => 'Sarana Ibadah',
        'ARENAOLAHRAGA_AR_50K_8.js' => 'Arena Olahraga',
    ];

    /**
     * Seed fasilitas from static GeoJSON JS files into the database.
     */
    public function run(): void
    {
        ini_set('memory_limit', '512M');

        // Pre-load category IDs
        $kategoriMap = KategoriFasilitas::pluck('id', 'nama')->toArray();
        $grandTotal = 0;

        foreach (self::FILE_CATEGORY_MAP as $filename => $kategoriNama) {
            $kategoriId = $kategoriMap[$kategoriNama] ?? null;

            if (!$kategoriId) {
                $this->command->warn("⚠ Kategori '{$kategoriNama}' not found in DB. Run KategoriFasilitasSeeder first. Skipping {$filename}.");
                continue;
            }

            try {
                $inserted = $this->processFile($filename, $kategoriId, $kategoriNama);
                $grandTotal += $inserted;
            } catch (\Exception $e) {
                $this->command->error("✗ Error processing {$filename}: " . $e->getMessage());
            }
        }

        $this->command->newLine();
        $this->command->info("Total inserted: {$grandTotal} facilities.");
    }

    /**
     * Process a single GeoJSON JS file and insert facilities.
     */
    private function processFile(string $filename, int $kategoriId, string $kategoriNama): int
    {
        $filePath = public_path("data/{$filename}");

        if (!file_exists($filePath)) {
            $this->command->warn("⚠ File not found: {$filePath}");
            return 0;
        }

        // Read and strip JS variable declaration
        $content = file_get_contents($filePath);
        $content = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
        $content = preg_replace('/;\s*$/', '', $content);

        $geojson = json_decode($content, true);

        if (!$geojson || !isset($geojson['features'])) {
            $this->command->warn("⚠ Failed to parse GeoJSON from {$filename}");
            return 0;
        }

        $features = $geojson['features'];
        $featureCount = count($features);
        $this->command->info("→ Processing: {$filename} ({$kategoriNama}) — {$featureCount} features");

        $inserted = 0;
        $skipped = 0;
        $nullKecamatan = 0;
        $chunk = [];
        $now = now()->toDateTimeString();
        $seenCoords = []; // Track coordinates within this run to avoid within-batch duplicates

        foreach ($features as $index => $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (!$geometry) {
                $skipped++;
                continue;
            }

            // Extract name — fallback for null NAMOBJ
            $nama = $properties['NAMOBJ']
                ?? $properties['REMARK']
                ?? "{$kategoriNama} #" . ($index + 1);

            // Extract coordinates based on geometry type
            [$latitude, $longitude] = $this->extractCoordinates($geometry);

            if ($latitude === null || $longitude === null) {
                $skipped++;
                continue;
            }

            // Unique key based on coordinates (no two different facilities share the exact GPS point)
            $coordKey = round($latitude, 8) . ',' . round($longitude, 8);

            // Skip if we already processed this coordinate in the current run
            if (isset($seenCoords[$coordKey])) {
                $skipped++;
                continue;
            }

            // Check for duplicate in database (exact match at column precision: decimal(11,8))
            $exists = DB::table('fasilitas')
                ->where('latitude', round($latitude, 8))
                ->where('longitude', round($longitude, 8))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $seenCoords[$coordKey] = true;

            // Auto-detect kecamatan_id via PostGIS ST_Contains
            $kecamatanId = $this->detectKecamatan($longitude, $latitude);

            if ($kecamatanId === null) {
                $nullKecamatan++;
            }

            $chunk[] = [
                'nama'          => $nama,
                'kategori_id'   => $kategoriId,
                'kecamatan_id'  => $kecamatanId,
                'latitude'      => round($latitude, 8),
                'longitude'     => round($longitude, 8),
                'foto'          => null,
                'deskripsi'     => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            // Insert in chunks of 100
            if (count($chunk) >= 100) {
                DB::table('fasilitas')->insert($chunk);
                $inserted += count($chunk);
                $chunk = [];
            }
        }

        // Insert remaining records
        if (count($chunk) > 0) {
            DB::table('fasilitas')->insert($chunk);
            $inserted += count($chunk);
        }

        $this->command->info("  ✓ Inserted: {$inserted} | Skipped: {$skipped} | Null kecamatan: {$nullKecamatan}");

        // Free memory
        unset($geojson, $features, $content);

        return $inserted;
    }

    /**
     * Extract latitude and longitude from a GeoJSON geometry.
     * Handles Point, Polygon, and MultiPolygon types.
     *
     * @return array{float|null, float|null} [latitude, longitude]
     */
    private function extractCoordinates(array $geometry): array
    {
        $type = $geometry['type'] ?? '';
        $coordinates = $geometry['coordinates'] ?? [];

        return match ($type) {
            'Point' => [
                (float) ($coordinates[1] ?? null),
                (float) ($coordinates[0] ?? null),
            ],
            'Polygon' => $this->calculateCentroid($coordinates[0] ?? []),
            'MultiPolygon' => $this->calculateCentroid($coordinates[0][0] ?? []),
            default => [null, null],
        };
    }

    /**
     * Calculate the centroid of a polygon ring (array of [lng, lat] pairs).
     *
     * @return array{float|null, float|null} [latitude, longitude]
     */
    private function calculateCentroid(array $ring): array
    {
        if (empty($ring)) {
            return [null, null];
        }

        $sumLng = 0.0;
        $sumLat = 0.0;
        $count = count($ring);

        foreach ($ring as $coord) {
            $sumLng += $coord[0];
            $sumLat += $coord[1];
        }

        return [
            round($sumLat / $count, 8),
            round($sumLng / $count, 8),
        ];
    }

    /**
     * Detect which kecamatan a point falls within using PostGIS ST_Contains.
     */
    private function detectKecamatan(float $longitude, float $latitude): ?int
    {
        try {
            $result = DB::selectOne("
                SELECT id FROM kecamatans
                WHERE geom IS NOT NULL
                  AND ST_Contains(
                      ST_GeomFromGeoJSON(geom::text),
                      ST_SetSRID(ST_MakePoint(?, ?), 4326)
                  )
                LIMIT 1
            ", [$longitude, $latitude]);

            return $result?->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}
