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
                ExportAction::make()
                    ->label('Exportar')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('clientes-'.now()->toDateString()),
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
