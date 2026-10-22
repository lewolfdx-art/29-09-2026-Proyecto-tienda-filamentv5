<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire
                            {--dry-run : Solo muestra cuántos pedidos se expirarían, sin cambiar nada}';

    protected $description = 'Expira los pedidos pendientes cuya reserva de stock ya venció';

    public function handle(): int
    {
        $pending = Order::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($this->option('dry-run')) {
            $this->info('Se expirarían '.$pending->count().' pedido(s).');

            return self::SUCCESS;
        }

        $expired = 0;

        $pending->eachById(function (Order $order) use (&$expired) {
            $order->update(['status' => 'expired']);

            $expired++;
        });

        $this->info('Se expiraron '.$expired.' pedido(s).');

        return self::SUCCESS;
    }
}
