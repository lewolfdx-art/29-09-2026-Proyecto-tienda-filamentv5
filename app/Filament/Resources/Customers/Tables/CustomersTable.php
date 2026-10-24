<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use STS\FilamentImpersonate\Actions\Impersonate;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono'),
                TextColumn::make('address')
                    ->label('Dirección'),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // El teléfono y la dirección se guardan cifrados, así que no admiten
            // un LIKE sobre la columna. La búsqueda global usa el índice ciego
            // que mantiene el modelo (ver Customer::scopeSearch).
            ->searchUsing(function (Builder $query, string $search): void {
                $query->search($search);
            })
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make('exportar_bi')
                    ->label('Exportar para Power BI')
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('id')->heading('customer_id'),
                                Column::make('name')->heading('customer_name'),
                                Column::make('email')->heading('email'),
                                Column::make('city')->heading('city'),
                                Column::make('created_at')
                                    ->heading('created_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                                Column::make('updated_at')
                                    ->heading('updated_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                            ])
                            ->withFilename('clientes_powerbi_'.now()->format('Y-m-d_His'))
                            ->queue(),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Impersonate::make()
                    ->guard('customer')
                    ->redirectTo(route('store.index')),
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
