<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->label('Vendedor')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('usd', true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
