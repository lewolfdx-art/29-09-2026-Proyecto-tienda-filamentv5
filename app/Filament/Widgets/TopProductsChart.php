<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopProductsChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'topProductsChart';

    protected static ?string $heading = 'Productos más vendidos (últimos 30 días)';

    protected static ?int $sort = 7;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', Order::COMMITTED_STATUSES)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->selectRaw('products.name as name, SUM(order_items.quantity) as total')
            ->groupBy('products.id', 'products.name')
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
                    'name' => 'Unidades',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->values()->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $rows->pluck('name')->values()->all(),
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
            'colors' => ['#67e8f9'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'barHeight' => '60%',
                ],
            ],
            'dataLabels' => ['enabled' => true],
            'grid' => ['borderColor' => 'rgba(148, 163, 184, 0.15)'],
            'noData' => ['text' => 'Sin ventas en este período'],
        ];
    }
}