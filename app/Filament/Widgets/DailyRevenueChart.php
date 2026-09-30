<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DailyRevenueChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'dailyRevenueChart';

    protected static ?string $heading = 'Ingresos por día (últimos 30 días)';

    protected static ?int $sort = 9;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $from = now()->subDays(29)->startOfDay();

        $rows = Order::whereIn('status', Order::COMMITTED_STATUSES)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i));

        $data = $days
            ->map(fn ($day) => round((float) ($rows[$day->toDateString()] ?? 0), 2))
            ->all();

        return [
            'chart' => [
                'type' => 'area',
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
                'categories' => $days->map(fn ($day) => $day->format('d/m'))->all(),
                'tickAmount' => 10,
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
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.4,
                    'opacityTo' => 0.05,
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
        ];
    }
}