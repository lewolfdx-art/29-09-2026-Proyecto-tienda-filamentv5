<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StoreStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $now = now();
        $d30 = $now->copy()->subDays(30);
        $d60 = $now->copy()->subDays(60);

        $stats = [];

        // La tarjeta de ingresos solo la ve quien puede ver el gráfico de ingresos.
        if (RevenueChart::canView()) {
            $revenue = $this->revenueBetween($d30, $now);
            $previousRevenue = $this->revenueBetween($d60, $d30);

            $stats[] = $this->buildStat(
                'Ingresos (30 días)',
                '$' . number_format($revenue, 2),
                $revenue,
                $previousRevenue,
                $this->dailySeries(
                    Order::query()->whereIn('status', Order::COMMITTED_STATUSES),
                    'SUM(total)',
                ),
            );
        }

        $customers = Customer::where('created_at', '>=', $d30)->count();
        $previousCustomers = Customer::whereBetween('created_at', [$d60, $d30])->count();

        $orders = Order::where('created_at', '>=', $d30)->count();
        $previousOrders = Order::whereBetween('created_at', [$d60, $d30])->count();

        $stats[] = $this->buildStat(
            'Nuevos clientes (30 días)',
            (string) $customers,
            $customers,
            $previousCustomers,
            $this->dailySeries(Customer::query(), 'COUNT(*)'),
        );

        $stats[] = $this->buildStat(
            'Nuevos pedidos (30 días)',
            (string) $orders,
            $orders,
            $previousOrders,
            $this->dailySeries(Order::query(), 'COUNT(*)'),
        );

        return $stats;
    }

    private function revenueBetween(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Order::whereIn('status', Order::COMMITTED_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');
    }

    private function buildStat(string $label, string $value, float $current, float $previous, array $chart): Stat
    {
        if ($previous > 0) {
            $percent = (($current - $previous) / $previous) * 100;
        } else {
            $percent = $current > 0 ? 100 : 0;
        }

        $percent = round($percent);

        [$text, $icon, $color] = match (true) {
            $percent > 0 => [abs($percent) . '% de aumento', 'heroicon-m-arrow-trending-up', 'success'],
            $percent < 0 => [abs($percent) . '% de disminución', 'heroicon-m-arrow-trending-down', 'danger'],
            default => ['Sin cambios', 'heroicon-m-minus', 'gray'],
        };

        return Stat::make($label, $value)
            ->description($text)
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($chart);
    }

    /**
     * Serie diaria de los últimos $days días (para el mini gráfico).
     */
    private function dailySeries(Builder $query, string $expression, int $days = 14): array
    {
        $rows = $query
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw("DATE(created_at) as day, {$expression} as value")
            ->groupBy('day')
            ->pluck('value', 'day');

        return collect(range($days - 1, 0))
            ->map(fn (int $i) => (float) ($rows[now()->subDays($i)->toDateString()] ?? 0))
            ->all();
    }
}