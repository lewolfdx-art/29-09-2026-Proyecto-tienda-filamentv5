<?php

namespace App\Support;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\EmailRecipient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class StoreNotifier
{
    /**
     * Roles que reciben avisos operativos (con y sin tilde por si acaso).
     */
    private const OPERATIONS_ROLES = ['super_admin', 'administrador', 'almacén', 'almacen'];

    /**
     * Roles que reciben el aviso de pedido pagado (operaciones + vendedor).
     */
    private const ORDER_PAID_ROLES = ['super_admin', 'administrador', 'almacén', 'almacen', 'vendedor'];

    /**
     * True solo cuando el stock BAJA y cruza el mínimo (o llega a cero),
     * para no avisar en cada venta.
     */
    public static function crossedLowStock(int $before, int $now, int $min): bool
    {
        if ($now >= $before) {
            return false;
        }

        return ($before > $min && $now <= $min)
            || ($before > 0 && $now <= 0);
    }

    public static function lowStock(Product $product): void
    {
        $soldOut = $product->stock <= 0;

        InternalNotifier::send(
            EmailRecipient::TYPE_LOW_STOCK,
            ($soldOut ? 'Producto agotado: ' : 'Stock bajo: ').$product->name,
            "Quedan {$product->stock} unidades (mínimo {$product->min_stock}).",
            ProductResource::getUrl('edit', ['record' => $product], panel: 'admin'),
            'Ver producto',
        );

        $recipients = self::usersWithRoles(self::OPERATIONS_ROLES);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(($soldOut ? 'Producto agotado: ' : 'Stock bajo: ').$product->name)
            ->body("Quedan {$product->stock} unidades (mínimo {$product->min_stock}).")
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor($soldOut ? 'danger' : 'warning')
            ->actions([
                Action::make('ver')
                    ->label('Ver producto')
                    ->url(ProductResource::getUrl('edit', ['record' => $product], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }

    public static function orderPlaced(Order $order): void
    {
        InternalNotifier::send(
            EmailRecipient::TYPE_NEW_ORDER,
            'Nuevo pedido #'.$order->id,
            'Cliente: '.($order->customer?->name ?? 'Sin cliente')
                .' — Total: '.Money::format($order->total),
            OrderResource::getUrl('view', ['record' => $order], panel: 'admin'),
            'Ver pedido',
        );
    }

    public static function paymentSubmitted(Order $order): void
    {
        InternalNotifier::send(
            EmailRecipient::TYPE_PAYMENT_PROOF,
            'Comprobante recibido: pedido #'.$order->id,
            'Cliente: '.($order->customer?->name ?? 'Sin cliente')
                .' — '.$order->paymentMethodLabel()
                .' — '.Money::format($order->total),
            OrderResource::getUrl('view', ['record' => $order], panel: 'admin'),
            'Revisar pago',
        );

        $recipients = self::usersWithRoles(self::OPERATIONS_ROLES);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Comprobante recibido: pedido #'.$order->id)
            ->body('Cliente: '.$order->customer->name.' · '.$order->paymentMethodLabel().' · '.Money::format($order->total))
            ->icon('heroicon-o-document-check')
            ->iconColor('info')
            ->actions([
                Action::make('ver')
                    ->label('Revisar pago')
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }

    public static function orderPaid(Order $order): void
    {
        $recipients = self::usersWithRoles(self::ORDER_PAID_ROLES);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Pedido #'.$order->id.' pagado')
            ->body('Cliente: '.$order->customer->name.' · Total: '.Money::format($order->total))
            ->icon('heroicon-o-banknotes')
            ->iconColor('success')
            ->actions([
                Action::make('ver')
                    ->label('Ver pedido')
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }

    private static function usersWithRoles(array $roles, ?int $exceptUserId = null): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->when($exceptUserId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->get();
    }
}
