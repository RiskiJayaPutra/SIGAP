<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Fasilitas;

class FasilitasPerKategoriChart extends ChartWidget
{
    protected ?string $heading = 'Total Fasilitas per Kategori';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $kategoriList = \App\Models\KategoriFasilitas::withCount('fasilitas')->get();
        
        $labels = [];
        $totals = [];
        $colors = ['#6366f1', '#ef4444', '#f59e0b', '#10b981', '#f97316', '#3b82f6', '#8b5cf6'];
        
        foreach ($kategoriList as $kategori) {
            $labels[] = $kategori->nama;
            $totals[] = $kategori->fasilitas_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Fasilitas',
                    'data' => $totals,
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
