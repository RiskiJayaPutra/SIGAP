<?php

namespace App\Console\Commands;

use App\Models\KategoriFasilitas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Fetch facility data for 5 new categories from OpenStreetMap Overpass API
 * and seed them into the fasilitas table with PostGIS kecamatan auto-detection.
 *
 * @throws \Exception
 */
class FetchFasilitasBaru extends Command
{
    protected $signature = 'sigap:fetch-fasilitas-baru
                            {--dry-run : Show what would be fetched without inserting}
                            {--timeout=90 : HTTP timeout in seconds}';

    protected $description = 'Fetch 5 new facility categories from OpenStreetMap Overpass API for Kota Bandar Lampung';

    /**
     * Bounding box for Kota Bandar Lampung.
     * Format: south, west, north, east
     */
    private const BBOX = '-5.52,105.15,-5.25,105.40';

    /**
     * Mapping: SIGAP category name → Overpass QL filter fragments.
     * Each entry can have multiple filters (OR union).
     */
    private const CATEGORY_QUERIES = [
        'Klinik' => [
            'node["amenity"="clinic"]({{bbox}});',
            'way["amenity"="clinic"]({{bbox}});',
        ],
        'Posyandu' => [
            'node["amenity"="community_centre"]["name"~"posyandu",i]({{bbox}});',
            'way["amenity"="community_centre"]["name"~"posyandu",i]({{bbox}});',
        ],
        'TK / PAUD' => [
            'node["amenity"="kindergarten"]({{bbox}});',
            'way["amenity"="kindergarten"]({{bbox}});',
        ],
        'Kuburan Umum' => [
            'node["landuse"="cemetery"]({{bbox}});',
            'way["landuse"="cemetery"]({{bbox}});',
            'node["amenity"="grave_yard"]({{bbox}});',
            'way["amenity"="grave_yard"]({{bbox}});',
        ],
        'Tempat Wisata' => [
            'node["tourism"="attraction"]({{bbox}});',
            'way["tourism"="attraction"]({{bbox}});',
            'node["tourism"="viewpoint"]({{bbox}});',
            'way["tourism"="viewpoint"]({{bbox}});',
            'node["leisure"="park"]({{bbox}});',
            'way["leisure"="park"]({{bbox}});',
        ],
    ];

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║  SIGAP — Fetch Fasilitas Baru dari OSM          ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        // Pre-load category IDs from DB
        $kategoriMap = KategoriFasilitas::pluck('id', 'nama')->toArray();
        $missingCategories = [];

        foreach (array_keys(self::CATEGORY_QUERIES) as $nama) {
            if (!isset($kategoriMap[$nama])) {
                $missingCategories[] = $nama;
            }
        }

        if (!empty($missingCategories)) {
            $this->error('Kategori berikut belum ada di database: ' . implode(', ', $missingCategories));
            $this->error('Jalankan: php artisan db:seed --class=KategoriBariSeeder');
            return Command::FAILURE;
        }

        // Build unified Overpass query
        $overpassQuery = $this->buildOverpassQuery();
        $this->info('→ Mengirim request ke Overpass API...');

        try {
            $elements = $this->fetchFromOverpass($overpassQuery);
        } catch (\Exception $e) {
            $this->error('✗ Gagal fetch dari Overpass API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("→ Diterima: " . count($elements) . " elemen dari OSM");
        $this->newLine();

        // Classify elements by category
        $classified = $this->classifyElements($elements);

        // Process and insert
        $grandTotal = 0;
        $isDryRun = $this->option('dry-run');

        $this->table(
            ['Kategori', 'Fetch', 'Skip (No Kec.)', 'Insert', 'Update'],
            collect($classified)->map(function ($items, $kategoriNama) use ($kategoriMap, $isDryRun, &$grandTotal) {
                $stats = $this->processCategory($kategoriNama, $items, $kategoriMap[$kategoriNama], $isDryRun);
                $grandTotal += $stats['inserted'] + $stats['updated'];
                return [
                    $kategoriNama,
                    $stats['fetched'],
                    $stats['skipped'],
                    $stats['inserted'],
                    $stats['updated'],
                ];
            })->values()->toArray()
        );

        $this->newLine();
        if ($isDryRun) {
            $this->warn("⚠ Dry-run mode — tidak ada data yang disimpan.");
        } else {
            $this->info("✓ Total disimpan: {$grandTotal} fasilitas baru/updated");
        }

        // Warn about sparse categories
        foreach ($classified as $kategoriNama => $items) {
            if (count($items) < 5) {
                $this->warn("⚠ Kategori '{$kategoriNama}' hanya memiliki " . count($items) . " hasil OSM — data mungkin sparse untuk Bandar Lampung.");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Build a single Overpass QL query that unions all category filters.
     */
    private function buildOverpassQuery(): string
    {
        $bbox = self::BBOX;
        $unionParts = [];

        foreach (self::CATEGORY_QUERIES as $fragments) {
            foreach ($fragments as $fragment) {
                $unionParts[] = str_replace('{{bbox}}', $bbox, $fragment);
            }
        }

        $union = implode("\n", $unionParts);

        return "[out:json][timeout:60];\n(\n{$union}\n);\nout center;\n";
    }

    /**
     * Send request to Overpass API and return parsed elements.
     *
     * @throws \Exception
     */
    private function fetchFromOverpass(string $query): array
    {
        $timeout = (int) $this->option('timeout');

        $response = Http::timeout($timeout)
            ->withoutVerifying()
            ->withHeaders([
                'User-Agent' => 'SIGAP-WebGIS/1.0',
                'Accept'     => '*/*',
            ])
            ->asForm()
            ->post('https://overpass-api.de/api/interpreter', [
                'data' => $query,
            ]);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}: " . $response->body());
        }

        $data = $response->json();

        if (!is_array($data) || !isset($data['elements'])) {
            throw new \Exception('Response JSON tidak memiliki key "elements".');
        }

        return $data['elements'];
    }

    /**
     * Classify raw OSM elements into SIGAP categories based on their tags.
     *
     * @return array<string, array> Keyed by category name
     */
    private function classifyElements(array $elements): array
    {
        $result = [];
        foreach (array_keys(self::CATEGORY_QUERIES) as $nama) {
            $result[$nama] = [];
        }

        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];
            $amenity  = $tags['amenity']  ?? '';
            $tourism  = $tags['tourism']  ?? '';
            $leisure  = $tags['leisure']  ?? '';
            $landuse  = $tags['landuse']  ?? '';
            $name     = $tags['name']     ?? '';

            // Classify in priority order
            if ($amenity === 'clinic') {
                $result['Klinik'][] = $el;
            } elseif ($amenity === 'community_centre' && stripos($name, 'posyandu') !== false) {
                $result['Posyandu'][] = $el;
            } elseif ($amenity === 'kindergarten') {
                $result['TK / PAUD'][] = $el;
            } elseif ($landuse === 'cemetery' || $amenity === 'grave_yard') {
                $result['Kuburan Umum'][] = $el;
            } elseif ($tourism === 'attraction' || $tourism === 'viewpoint' || $leisure === 'park') {
                $result['Tempat Wisata'][] = $el;
            }
        }

        return $result;
    }

    /**
     * Process a single category: resolve coordinates, detect kecamatan, upsert.
     *
     * @return array{fetched: int, skipped: int, inserted: int, updated: int}
     */
    private function processCategory(string $kategoriNama, array $elements, int $kategoriId, bool $isDryRun): array
    {
        $stats = ['fetched' => count($elements), 'skipped' => 0, 'inserted' => 0, 'updated' => 0];

        if ($isDryRun || empty($elements)) {
            return $stats;
        }

        DB::transaction(function () use ($elements, $kategoriNama, $kategoriId, &$stats) {
            foreach ($elements as $el) {
                $tags = $el['tags'] ?? [];
                $osmId = $el['id'] ?? 0;

                // Extract coordinates
                [$lat, $lon] = $this->extractCoords($el);

                if ($lat === null || $lon === null) {
                    $stats['skipped']++;
                    continue;
                }

                // Nama: use OSM name, fallback to "[Kategori] #OSM_ID"
                $nama = $tags['name'] ?? "{$kategoriNama} #{$osmId}";

                // Alamat: concat addr:street + addr:housenumber, fallback description
                $alamat = null;
                if (!empty($tags['addr:street'])) {
                    $alamat = $tags['addr:street'];
                    if (!empty($tags['addr:housenumber'])) {
                        $alamat .= ' ' . $tags['addr:housenumber'];
                    }
                } elseif (!empty($tags['description'])) {
                    $alamat = $tags['description'];
                }

                // Detect kecamatan via PostGIS ST_Contains
                $kecamatanId = $this->detectKecamatan($lon, $lat);

                if ($kecamatanId === null) {
                    $this->warn("  ⚠ Skip '{$nama}' — tidak termasuk dalam kecamatan manapun ({$lat}, {$lon})");
                    $stats['skipped']++;
                    continue;
                }

                // Upsert: updateOrCreate keyed on nama + kecamatan + kategori
                $fasilitas = \App\Models\Fasilitas::updateOrCreate(
                    [
                        'nama'         => $nama,
                        'kecamatan_id' => $kecamatanId,
                        'kategori_id'  => $kategoriId,
                    ],
                    [
                        'latitude'  => round($lat, 8),
                        'longitude' => round($lon, 8),
                        'deskripsi' => $alamat,
                    ]
                );

                if ($fasilitas->wasRecentlyCreated) {
                    $stats['inserted']++;
                } else {
                    $stats['updated']++;
                }
            }
        });

        return $stats;
    }

    /**
     * Extract lat/lon from an Overpass element.
     * Nodes have lat/lon directly; ways use center.lat/center.lon.
     *
     * @return array{float|null, float|null} [lat, lon]
     */
    private function extractCoords(array $element): array
    {
        // Node: direct lat/lon
        if (isset($element['lat'], $element['lon'])) {
            return [(float) $element['lat'], (float) $element['lon']];
        }

        // Way/Relation: use center (from "out center")
        if (isset($element['center']['lat'], $element['center']['lon'])) {
            return [(float) $element['center']['lat'], (float) $element['center']['lon']];
        }

        return [null, null];
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
