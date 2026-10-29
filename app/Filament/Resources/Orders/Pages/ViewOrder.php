<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Mail\PaymentRejected;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

/**
 * @extends ViewRecord<Order>
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approvePayment')
                ->label('Aprobar pago')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar el pago del pedido')
                ->modalDescription(fn (Order $record): string => "El pedido #{$record->id} se marcará como pagado y se descontará el stock.")
                ->modalSubmitActionLabel('Sí, aprobar pago')
                ->visible(fn (Order $record): bool => $record->status === 'pending'
                    && filled($record->payment_submitted_at)
                    && blank($record->payment_rejection_reason))
                ->action(function (Order $record): void {
                    $missing = $record->productsWithoutStock();

                    if (! empty($missing)) {
                        Notification::make()
                            ->title('Stock insuficiente')
                            ->body(implode(', ', $missing))
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->update([
                        'status' => 'paid',
                        'payment_verified_at' => now(),
                        'payment_verified_by' => auth('web')->id(),
                        'payment_rejection_reason' => null,
                    ]);

                    Notification::make()
                        ->title('Pago aprobado')
                        ->body("El pedido #{$record->id} quedó marcado como pagado.")
                        ->success()
                        ->send();
                }),
            Action::make('rejectPayment')
                ->label('Rechazar comprobante')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rechazar el comprobante')
                ->modalDescription('El cliente verá el motivo y podrá subir un comprobante nuevo.')
                ->modalSubmitActionLabel('Rechazar comprobante')
                ->visible(fn (Order $record): bool => $record->status === 'pending'
                    && filled($record->payment_submitted_at)
                    && blank($record->payment_rejection_reason))
                ->schema([
                    Textarea::make('reason')
                        ->label('Motivo del rechazo')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('Ej. El monto no coincide con el total del pedido.'),
                ])
                ->action(function (array $data, Order $record): void {
                    $record->update([
                        'payment_rejection_reason' => $data['reason'],
                        'payment_submitted_at' => null,
                        'payment_verified_at' => null,
                        'payment_verified_by' => null,
                    ]);

                    if ($email = $record->customer?->email) {
                        Mail::to($email)->queue(new PaymentRejected($record, $data['reason']));
                    }

                    Notification::make()
                        ->title('Comprobante rechazado')
                        ->body("Se notificó al cliente del pedido #{$record->id}.")
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
