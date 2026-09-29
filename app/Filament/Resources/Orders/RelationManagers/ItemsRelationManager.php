<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Artículos';

    protected static ?string $recordTitleAttribute = 'product_id';

    /**
     * Solo quien puede editar el pedido puede tocar sus artículos.
     */
    protected function canManageItems(): bool
    {
        return auth()->user()?->can('update', $this->getOwnerRecord()) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship(
                        'product',
                        'name',
                        fn ($query, $record) => $query
                            ->where('is_active', true)
                            ->when($record?->product_id, fn ($q, $id) => $q->orWhere('products.id', $id)),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('unit_price', Product::find($state)?->price);
                    }),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                TextInput::make('unit_price')
                    ->label('Precio unitario (automático)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto'),
                TextColumn::make('quantity')
                    ->label('Cantidad'),
                TextColumn::make('unit_price')
                    ->label('Precio unitario')
                    ->money(),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->state(fn ($record) => $record->quantity * $record->unit_price)
                    ->money(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->canManageItems()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => $this->canManageItems()),
                DeleteAction::make()
                    ->visible(fn () => $this->canManageItems()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => $this->canManageItems()),
                ]),
            ]);
    }
}