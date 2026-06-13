<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Fasilitas;
use App\Models\Kecamatan;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalFasilitas = Fasilitas::count();
        $totalKecamatan = Kecamatan::whereNotNull('populasi')->count();
        $totalPenduduk = Kecamatan::sum('populasi');
        $totalLuas = Kecamatan::sum('luas_wilayah');

        return [
            Stat::make('Total Fasilitas', number_format($totalFasilitas))
                ->description('Tercatat di seluruh wilayah')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Total Penduduk', number_format($totalPenduduk, 0, ',', '.') . ' Jiwa')
                ->description($totalKecamatan . ' kecamatan tercatat')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Total Luas Wilayah', number_format($totalLuas, 2, ',', '.') . ' km²')
                ->description('Keseluruhan area tercatat')
                ->descriptionIcon('heroicon-o-map')
                ->color('warning'),

            Stat::make('Kecamatan', number_format($totalKecamatan))
                ->description('Wilayah administratif')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('info'),
        ];
    }
}
