<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\InventoryMovement;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public_products')
                    ->square()
                    ->defaultImageUrl(fn () => null)
                    ->height('32'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->money('gtq', true)
                    ->label('Precio')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Vence')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
                Action::make('adjustStock')
                    ->label('Ajustar stock')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad (+/-)')
                            ->numeric()
                            ->required()
                            ->hint('Usa valores negativos para descontar'),
                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('Nota')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $change = (int) $data['quantity'];

                        if ($change === 0) {
                            Notification::make()
                                ->title('No se aplicó ningún cambio de stock')
                                ->warning()
                                ->send();

                            return;
                        }

                        $currentStock = (int) $record->stock;
                        $newStock = max(0, $currentStock + $change);
                        $appliedChange = $newStock - $currentStock;

                        $record->update(['stock' => $newStock]);

                        InventoryMovement::create([
                            'product_id' => $record->id,
                            'user_id' => Auth::id(),
                            'type' => 'adjustment',
                            'quantity' => $appliedChange,
                            'note' => $data['note'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Stock actualizado')
                            ->body("Stock ahora: {$newStock}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => Auth::user()?->isAdmin()),
            ]);
    }
}
