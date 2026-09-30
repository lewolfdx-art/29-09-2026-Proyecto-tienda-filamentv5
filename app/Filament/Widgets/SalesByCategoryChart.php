<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SalesByCategoryChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'salesByCategoryChart';

    protected static ?string $heading = 'Ingresos por categoría (últimos 30 días)';

    protected static ?int $sort = 8;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', Order::COMMITTED_STATUSES)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->selectRaw("COALESCE(categories.name, 'Sin categoría') as category, SUM(order_items.quantity * order_items.unit_price) as total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 320,
            ],
            'series' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->values()->all(),
            'labels' => $rows->pluck('category')->values()->all(),
            'colors' => ['#22d3ee', '#38bdf8', '#34d399', '#a78bfa', '#fbbf24', '#f87171', '#94a3b8', '#67e8f9'],
            'stroke' => ['width' => 0],
            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '70%',
                        'labels' => [
                            'show' => true,
                            'name' => ['color' => '#94a3b8'],
                            'value' => ['color' => '#e2e8f0'],
                            'total' => [
                                'show' => true,
                                'label' => 'Total',
                                'color' => '#94a3b8',
                            ],
                        ],
                    ],
                ],
            ],
            'noData' => ['text' => 'Sin ventas en este período'],
        ];
    }
}