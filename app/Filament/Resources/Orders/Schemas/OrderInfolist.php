<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cliente'),
                TextEntry::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => Order::STATUS_LABELS[$state] ?? $state),
                TextEntry::make('total')
                    ->label('Total')
                    ->money('PEN'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->label('Ciudad')
                    ->placeholder('-'),
                TextEntry::make('payment_method')
                    ->label('Método de pago')
                    ->formatStateUsing(fn (?string $state): ?string => $state
                        ? (config("payments.methods.{$state}.label") ?? ucfirst($state))
                        : null)
                    ->placeholder('-'),
                TextEntry::make('payment_reference')
                    ->label('Nº de operación')
                    ->placeholder('-'),
                TextEntry::make('payment_submitted_at')
                    ->label('Comprobante enviado')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('payment_proof_path')
                    ->label('Comprobante')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? 'Ver comprobante' : null)
                    ->url(fn (Order $record): ?string => $record->paymentProofUrl())
                    ->openUrlInNewTab()
                    ->placeholder('-'),
                TextEntry::make('payment_verified_at')
                    ->label('Pago verificado')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('paymentVerifier.name')
                    ->label('Verificado por')
                    ->placeholder('-'),
                TextEntry::make('payment_rejection_reason')
                    ->label('Motivo de rechazo')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
