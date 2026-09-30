<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SalesByCityChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'salesByCityChart';

    protected static ?string $heading = 'Ingresos por ciudad (año actual)';

    protected static ?int $sort = 10;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = Order::query()
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->whereIn('orders.status', Order::COMMITTED_STATUSES)
            ->whereYear('orders.created_at', now()->year)
            ->selectRaw("COALESCE(NULLIF(customers.city, ''), 'Sin ciudad') as city, SUM(orders.total) as total")
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Ingresos',
                    'data' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->values()->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $rows->pluck('city')->values()->all(),
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
            'colors' => ['#38bdf8'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth' => '55%',
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
            'noData' => ['text' => 'Sin ventas este año'],
        ];
    }
}