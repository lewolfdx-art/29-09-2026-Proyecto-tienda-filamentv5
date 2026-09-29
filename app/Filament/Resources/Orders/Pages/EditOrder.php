<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $order = $this->getRecord();
        $newStatus = $this->data['status'] ?? $order->status;

        $wasCommitted = in_array($order->status, Order::COMMITTED_STATUSES);
        $willBeCommitted = in_array($newStatus, Order::COMMITTED_STATUSES);

        if ($wasCommitted || ! $willBeCommitted) {
            return;
        }

        $missing = $order->productsWithoutStock();

        if (! empty($missing)) {
            Notification::make()
                ->title('Stock insuficiente')
                ->body(implode(', ', $missing))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}