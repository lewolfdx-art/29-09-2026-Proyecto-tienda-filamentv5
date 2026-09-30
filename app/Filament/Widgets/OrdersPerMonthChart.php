<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OrdersPerMonthChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'ordersPerMonthChart';

    protected static ?string $heading = 'Pedidos por mes (año actual)';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = Order::whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = collect(range(1, 12))
            ->map(fn (int $m) => (int) ($rows[$m] ?? 0))
            ->all();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Pedidos',
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
                'decimalsInFloat' => 0,
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],
            'colors' => ['#67e8f9'],
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