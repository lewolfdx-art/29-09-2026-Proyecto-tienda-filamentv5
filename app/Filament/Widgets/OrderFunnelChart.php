<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OrderFunnelChart extends ApexChartWidget
{
    use HasWidgetShield;

    protected static ?string $chartId = 'orderFunnelChart';

    protected static ?string $heading = 'Embudo de pedidos (últimos 30 días)';

    protected static ?int $sort = 11;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $since = now()->subDays(30);

        // Aproximación: el estado guarda solo el estado actual, no el historial.
        // Cada etapa cuenta los pedidos que están en ella o en una posterior.
        $created = Order::where('created_at', '>=', $since)->count();

        $paid = Order::where('created_at', '>=', $since)
            ->whereIn('status', Order::COMMITTED_STATUSES)
            ->count();

        $shipped = Order::where('created_at', '>=', $since)
            ->whereIn('status', ['shipped', 'delivered'])
            ->count();

        $delivered = Order::where('created_at', '>=', $since)
            ->where('status', 'delivered')
            ->count();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Pedidos',
                    'data' => [$created, $paid, $shipped, $delivered],
                ],
            ],
            'xaxis' => [
                'categories' => ['Creados', 'Pagados', 'Enviados', 'Entregados'],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'colors' => ['#94a3b8', '#22d3ee', '#38bdf8', '#34d399'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'isFunnel' => true,
                    'distributed' => true,
                    'barHeight' => '80%',
                ],
            ],
            'dataLabels' => ['enabled' => true],
            'legend' => ['show' => false],
            'grid' => ['show' => false],
            'noData' => ['text' => 'Sin pedidos en este período'],
        ];
    }
}