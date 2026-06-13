<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Kecamatan;
use App\Models\Fasilitas;

class PersentaseKecamatanChart extends ChartWidget
{
    protected ?string $heading = 'Persentase Fasilitas per Kecamatan';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $kecamatans = Kecamatan::withCount('fasilitas')
            ->orderBy('nama')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($kecamatans as $kec) {
            if ($kec->fasilitas_count > 0) {
                $labels[] = $kec->nama;
                $counts[] = $kec->fasilitas_count;
            }
        }

        // Hitung persentase
        $grandTotal = array_sum($counts);
        $percentages = array_map(function ($c) use ($grandTotal) {
            return $grandTotal > 0 ? round(($c / $grandTotal) * 100, 1) : 0;
        }, $counts);

        return [
            'datasets' => [
                [
                    'label' => 'Persentase (%)',
                    'data' => $percentages,
                    'backgroundColor' => [
                        '#6366f1', '#8b5cf6', '#a855f7', '#ec4899', '#f43f5e',
                        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
                        '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
                        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#c084fc',
                        '#d946ef', '#f472b6', '#fb923c', '#facc15', '#a3e635',
                        '#34d399', '#2dd4bf', '#22d3ee', '#38bdf8', '#818cf8',
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.label + ': ' + context.parsed + '%'; }",
                    ],
                ],
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 8,
                        'font' => ['size' => 11],
                    ],
                ],
            ],
        ];
    }
}
