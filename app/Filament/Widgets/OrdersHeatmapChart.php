<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OrdersHeatmapChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'ordersHeatmapChart';

    protected static ?string $heading = 'Pedidos por día y hora (últimos 90 días)';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = Order::where('created_at', '>=', now()->subDays(90))
            ->selectRaw('DAYOFWEEK(created_at) as dow, HOUR(created_at) as hour, COUNT(*) as total')
            ->groupBy('dow', 'hour')
            ->get();

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[(int) $row->dow][(int) $row->hour] = (int) $row->total;
        }

        // DAYOFWEEK de MySQL: 1 = domingo ... 7 = sábado.
        $names = [1 => 'Dom', 2 => 'Lun', 3 => 'Mar', 4 => 'Mié', 5 => 'Jue', 6 => 'Vie', 7 => 'Sáb'];

        // ApexCharts dibuja la primera serie abajo, así que van en orden inverso
        // para que el lunes quede arriba.
        $series = [];
        foreach ([1, 7, 6, 5, 4, 3, 2] as $dow) {
            $series[] = [
                'name' => $names[$dow],
                'data' => collect(range(0, 23))
                    ->map(fn (int $h) => [
                        'x' => sprintf('%02d h', $h),
                        'y' => $matrix[$dow][$h] ?? 0,
                    ])
                    ->all(),
            ];
        }

        return [
            'chart' => [
                'type' => 'heatmap',
                'height' => 340,
                'toolbar' => ['show' => false],
            ],
            'series' => $series,
            'xaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'colors' => ['#22d3ee'],
            'stroke' => ['width' => 1],
            'plotOptions' => [
                'heatmap' => [
                    'radius' => 3,
                    'shadeIntensity' => 0.6,
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
            'noData' => ['text' => 'Sin pedidos en este período'],
        ];
    }
}