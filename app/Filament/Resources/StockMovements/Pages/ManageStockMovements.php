<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStockMovements extends ManageRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \App\Models\StockMovement {
                    try {
                        return app(\App\Services\Stocks\StockService::class)->manual(
                            \App\Models\Product::findOrFail($data['product_id']),
                            (int) $data['quantity'], $data['type'], $data['reason'], $data['reference'],
                        );
                    } catch (\DomainException $exception) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => $exception->getMessage()]);
                    }
                }),
            \Filament\Actions\Action::make('count')
                ->label('Contagem física')
                ->authorize(fn (): bool => StockMovementResource::canCreate())
                ->schema([
                    \Filament\Forms\Components\Select::make('product_id')->label('Produto')->options(fn () => \App\Models\Product::pluck('name', 'id'))->searchable()->required(),
                    \Filament\Forms\Components\TextInput::make('balance')->label('Saldo contado')->integer()->minValue(0)->required(),
                    \Filament\Forms\Components\TextInput::make('description')->label('Motivo')->maxLength(255)->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $movement = app(\App\Services\Stocks\StockService::class)->count(
                            \App\Models\Product::findOrFail($data['product_id']), (int) $data['balance'], $data['description'],
                        );
                    } catch (\DomainException $exception) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['balance' => $exception->getMessage()]);
                    }
                    \Filament\Notifications\Notification::make()->title($movement ? 'Saldo ajustado com histórico.' : 'Saldo conferido, sem diferença.')->success()->send();
                }),
        ];
    }
}
