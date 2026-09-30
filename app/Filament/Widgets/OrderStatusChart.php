<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OrderStatusChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'orderStatusChart';

    protected static ?string $heading = 'Estado de pedidos (últimos 30 días)';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $statuses = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ];

        $counts = Order::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 320,
            ],
            'series' => collect(array_keys($statuses))
                ->map(fn (string $status) => (int) ($counts[$status] ?? 0))
                ->values()
                ->all(),
            'labels' => array_values($statuses),
            'colors' => ['#94a3b8', '#22d3ee', '#38bdf8', '#34d399', '#f87171'],
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
        ];
    }
}