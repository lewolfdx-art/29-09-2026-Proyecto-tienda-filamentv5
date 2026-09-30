<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'revenueChart';

    protected static ?string $heading = 'Ingresos por mes (año actual)';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = Order::whereIn('status', Order::COMMITTED_STATUSES)
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = collect(range(1, 12))
            ->map(fn (int $m) => round((float) ($rows[$m] ?? 0), 2))
            ->all();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Ingresos',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],
            'colors' => ['#22d3ee'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth' => '55%',
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
        ];
    }
}