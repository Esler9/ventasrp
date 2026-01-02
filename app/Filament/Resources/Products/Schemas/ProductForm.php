<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
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
                TextInput::make('sku')
                    ->label('SKU / Código')
                    ->required()
                    ->unique(Product::class, 'sku', ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('price')
                    ->label('Precio')
                    ->prefix('Q')
                    ->numeric()
                    ->required()
                    ->rule('min:0'),
                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),
                DatePicker::make('expires_at')
                    ->label('Fecha de vencimiento')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->nullable(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
