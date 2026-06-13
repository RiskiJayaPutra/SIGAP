<?php

namespace Database\Seeders;

use App\Models\KategoriFasilitas;
use Illuminate\Database\Seeder;

class KategoriFasilitasSeeder extends Seeder
{
    /**
     * Seed kategori fasilitas (idempotent via updateOrCreate).
     */
    public function run(): void
    {
        $categories = [
            ['nama' => 'Pendidikan',     'icon' => 'school.png',   'warna' => '#3B82F6'],
            ['nama' => 'Rumah Sakit',    'icon' => 'hospital.png', 'warna' => '#EF4444'],
            ['nama' => 'SPBU',           'icon' => 'gas.png',      'warna' => '#F59E0B'],
            ['nama' => 'Sarana Ibadah',  'icon' => 'mosque.png',   'warna' => '#10B981'],
            ['nama' => 'Arena Olahraga', 'icon' => 'sport.png',    'warna' => '#8B5CF6'],
        ];

        foreach ($categories as $cat) {
            KategoriFasilitas::updateOrCreate(
                ['nama' => $cat['nama']],
                ['icon' => $cat['icon'], 'warna' => $cat['warna']]
            );
        }

        $this->command->info('✓ ' . count($categories) . ' kategori fasilitas seeded.');
    }
}
