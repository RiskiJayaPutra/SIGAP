<?php

namespace Database\Seeders;

use App\Models\KategoriFasilitas;
use Illuminate\Database\Seeder;

class KategoriBariSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nama' => 'Klinik',        'icon' => 'clinic.png',        'warna' => '#06B6D4'],
            ['nama' => 'Posyandu',      'icon' => 'posyandu.png',      'warna' => '#EC4899'],
            ['nama' => 'TK / PAUD',     'icon' => 'kindergarten.png',  'warna' => '#F97316'],
            ['nama' => 'Kuburan Umum',  'icon' => 'cemetery.png',      'warna' => '#6B7280'],
            ['nama' => 'Tempat Wisata', 'icon' => 'tourism.png',       'warna' => '#84CC16'],
        ];

        foreach ($categories as $cat) {
            KategoriFasilitas::firstOrCreate(
                ['nama' => $cat['nama']],
                ['icon' => $cat['icon'], 'warna' => $cat['warna']]
            );
        }

        $this->command->info('✓ 5 kategori baru seeded:');
        foreach ($categories as $cat) {
            $this->command->info("  - {$cat['nama']} ({$cat['warna']})");
        }
    }
}
