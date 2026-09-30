<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockProducts extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos con stock bajo')
            ->description('Productos activos que llegaron a su stock mínimo o están agotados.')
            ->query(fn (): Builder => Product::query()->active()->lowStock())
            ->defaultSort('stock')
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->disk('public')
                    ->height(40)
                    ->square(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoría'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (Product $record) => $record->stock <= 0 ? 'danger' : 'warning')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Mínimo'),
            ])
            ->recordUrl(fn (Product $record): ?string => auth()->user()?->can('update', $record)
                ? ProductResource::getUrl('edit', ['record' => $record])
                : null)
            ->paginated([5])
            ->emptyStateHeading('Todo el stock está en orden')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}