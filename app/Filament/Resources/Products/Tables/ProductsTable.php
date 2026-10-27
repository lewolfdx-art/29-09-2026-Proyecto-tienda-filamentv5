<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ProductsTable
{
    protected static function canUpdate(Model $record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->can('update', $record) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_thumb_url')
                    ->label('Imagen')
                    ->height(50)
                    ->square(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                ToggleColumn::make('is_featured')
                    ->label('Destacado')
                    ->disabled(fn (Product $record) => ! self::canUpdate($record)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Ocultos')
                    ->default(true),
                Filter::make('low_stock')
                    ->label('Stock bajo')
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),
                Filter::make('featured')
                    ->label('Solo destacados')
                    ->query(fn (Builder $query) => $query->where('is_featured', true))
                    ->toggle(),
            ])
            ->headerActions([
                ExportAction::make('exportar_bi')
                    ->label('Exportar para Power BI')
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('id')->heading('product_id'),
                                Column::make('category_id')->heading('category_id'),
                                Column::make('category.name')->heading('category_name'),
                                Column::make('name')->heading('product_name'),
                                Column::make('sku')->heading('sku'),
                                Column::make('price')->heading('price'),
                                Column::make('stock')->heading('stock'),
                                Column::make('min_stock')->heading('min_stock'),
                                Column::make('is_active')->heading('is_active'),
                                Column::make('is_featured')->heading('is_featured'),
                                Column::make('created_at')
                                    ->heading('created_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                                Column::make('updated_at')
                                    ->heading('updated_at')
                                    ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
                            ])
                            ->withFilename('productos_powerbi_'.now()->format('Y-m-d_His'))
                            ->queue(),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Product $record) => $record->is_active ? 'Ocultar' : 'Mostrar')
                    ->icon(fn (Product $record) => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
                    ->color(fn (Product $record) => $record->is_active ? 'warning' : 'success')
                    ->visible(fn (Product $record) => self::canUpdate($record))
                    ->requiresConfirmation()
                    ->action(fn (Product $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar seleccionados'),
                    BulkAction::make('hide')
                        ->label('Ocultar seleccionados')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->color('warning')
                        ->visible(fn () => self::canUpdate(new Product))
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('show')
                        ->label('Mostrar seleccionados')
                        ->icon(Heroicon::OutlinedEye)
                        ->color('success')
                        ->visible(fn () => self::canUpdate(new Product))
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
