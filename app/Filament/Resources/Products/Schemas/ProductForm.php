<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug (automático)')
                    ->disabled()
                    ->dehydrated(false)
                    ->hiddenOn('create'),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),
                TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('stock')
                    ->label('Stock')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('min_stock')
                    ->label('Stock mínimo')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(5)
                    ->helperText('Cuando el stock llegue a este número, el producto aparece como stock bajo.'),
                FileUpload::make('image')
                    ->label('Imagen')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('products'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Toggle::make('is_featured')
                    ->label('Destacado')
                    ->helperText('Aparece en el carrusel 3D de la tienda (se muestran hasta 10).')
                    ->default(false),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }
}