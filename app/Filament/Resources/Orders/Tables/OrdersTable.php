<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'shipped', 'delivered' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state
                        ? (config("payments.methods.{$state}.label") ?? ucfirst($state))
                        : null)
                    ->placeholder('—'),
                IconColumn::make('payment_proof_path')
                    ->label('Comprobante')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('payment_submitted_at')
                    ->label('Comprobante enviado')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('payment_review')
                    ->label('Comprobantes por revisar')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereNotNull('payment_submitted_at')
                        ->whereNull('payment_rejection_reason'))
                    ->toggle(),
            ])
            ->headerActions([
                ExportAction::make('exportar_bi')
                    ->label('Exportar para Power BI')
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('id')->heading('order_id'),
                                Column::make('customer_id')->heading('customer_id'),
                                Column::make('customer.name')->heading('customer_name'),
                                Column::make('status')->heading('status'),
                                Column::make('total')->heading('total_amount'),
                                Column::make('created_at')
                                    ->heading('order_date')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                                Column::make('updated_at')
                                    ->heading('updated_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                                Column::make('expires_at')
                                    ->heading('expires_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                            ])
                            ->withFilename('pedidos_powerbi_'.now()->format('Y-m-d_His'))
                            ->queue(),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar seleccionados'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
