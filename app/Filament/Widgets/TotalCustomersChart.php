<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TotalCustomersChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'totalCustomersChart';

    protected static ?string $heading = 'Total de clientes (año actual)';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $currentMonth = now()->month;

        $running = Customer::where('created_at', '<', now()->startOfYear())->count();

        $rows = Customer::whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = [];
        foreach (range(1, $currentMonth) as $m) {
            $running += (int) ($rows[$m] ?? 0);
            $data[] = $running;
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Clientes',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => array_slice($months, 0, $currentMonth),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],
            'colors' => ['#67e8f9'],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'markers' => ['size' => 4],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
        ];
    }
}