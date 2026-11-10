<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Sales\Pages\ManageSales;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends BaseAclResource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|null|\UnitEnum $navigationGroup = 'Vendas';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $modelLabel = 'Venda';

    protected static ?string $pluralModelLabel = 'Vendas';

    protected static string $permissionEntity = 'sales';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Venda')
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente (CPF)')
                            ->relationship('client', 'cpf')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('Vendedor')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('client.cpf')
                    ->label('CPF do Cliente'),

                TextEntry::make('user.name')
                    ->label('Vendedor'),

                TextEntry::make('total')
                    ->label('Total')
                    ->money('BRL'),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Rascunho',
                        'confirmed' => 'Confirmada',
                        'canceled' => 'Cancelada',
                        default => $state,
                    }),

                TextEntry::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Sale::PAYMENT_STATUS_LABELS[$state] ?? (string) $state),

                TextEntry::make('payments_summary')
                    ->label('Recebimentos')
                    ->state(function (Sale $record): string {
                        $record->loadMissing('payments');
                        if ($record->payments->isEmpty()) {
                            return '—';
                        }

                        return $record->payments
                            ->map(function ($payment): string {
                                $method = SalePayment::METHOD_LABELS[$payment->method] ?? $payment->method;
                                $type = $payment->type === 'refund' ? 'Estorno' : 'Pagamento';
                                $amount = 'R$ '.number_format((float) $payment->amount, 2, ',', '.');

                                return "{$type} · {$method} · {$amount}";
                            })
                            ->implode("\n");
                    })
                    ->html(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Nº')
                    ->formatStateUsing(fn ($state): string => '#'.str_pad((string) $state, 4, '0', STR_PAD_LEFT))
                    ->sortable(),
                TextColumn::make('origin')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'chatbot' => 'Chat',
                        default => 'PDV',
                    })
                    ->colors([
                        'info' => 'chatbot',
                        'gray' => fn (?string $state): bool => $state !== 'chatbot',
                    ])
                    ->sortable(),
                TextColumn::make('client.cpf')
                    ->label('CPF Cliente')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('buyer_name')
                    ->label('Contato (chat)')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Rascunho',
                        'confirmed' => 'Confirmada',
                        'canceled' => 'Cancelada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'confirmed',
                        'danger' => 'canceled',
                    ]),
                TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Sale::PAYMENT_STATUS_LABELS[$state] ?? (string) $state)
                    ->colors([
                        'warning' => Sale::PAYMENT_UNPAID,
                        'success' => Sale::PAYMENT_PAID,
                        'danger' => Sale::PAYMENT_REFUNDED,
                    ]),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->authorize(fn (Sale $record): bool => static::canEdit($record)),
                Action::make('receipt')
                    ->label('Comprovante')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (Sale $record): string => \App\Filament\Pages\SaleReceipt::getUrl(['sale' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn (Sale $record): bool => in_array($record->status, ['confirmed', 'canceled'], true) && ! $record->trashed()),
                Action::make('returnItems')
                    ->label('Devolver itens')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->modalHeading('Devolução parcial')
                    ->fillForm(function (Sale $record): array {
                        $record->load(['items.product', 'returns.items']);
                        $form = [
                            'destination' => \App\Models\SaleReturn::DESTINATION_RESTOCK,
                            'reason' => '',
                            'refund_method' => 'pix',
                            'refund_amount' => '0',
                        ];
                        foreach ($record->items as $item) {
                            $form['qty_'.$item->id] = 0;
                        }

                        return $form;
                    })
                    ->schema(function (Sale $record): array {
                        $record->load(['items.product', 'returns.items', 'payments']);
                        $fields = [];
                        foreach ($record->items as $item) {
                            $remaining = $record->remainingQuantityForItem($item);
                            if ($remaining <= 0) {
                                continue;
                            }
                            $fields[] = TextInput::make('qty_'.$item->id)
                                ->label(($item->product?->name ?? 'Item #'.$item->id).' (restante '.$remaining.')')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($remaining)
                                ->default(0)
                                ->required();
                        }
                        $fields[] = Select::make('destination')
                            ->label('Destino do produto')
                            ->options(\App\Models\SaleReturn::DESTINATION_LABELS)
                            ->required();
                        if ($record->payment_status === Sale::PAYMENT_PAID) {
                            $fields[] = Select::make('refund_method')
                                ->label('Meio do estorno')
                                ->options(SalePayment::METHOD_LABELS)
                                ->required();
                            $fields[] = TextInput::make('refund_amount')
                                ->label('Valor a estornar (0 = sem estorno agora)')
                                ->numeric()
                                ->minValue(0)
                                ->required();
                        }
                        $fields[] = TextInput::make('reason')
                            ->label('Motivo da devolução')
                            ->required()
                            ->maxLength(255);

                        return $fields;
                    })
                    ->authorize(function (Sale $record): bool {
                        if (! auth()->user()?->can('return-sales')
                            || $record->status !== 'confirmed'
                            || $record->payment_status === Sale::PAYMENT_REFUNDED
                            || $record->trashed()) {
                            return false;
                        }
                        $record->loadMissing(['items', 'returns.items']);

                        return $record->items->contains(
                            fn ($item): bool => $record->remainingQuantityForItem($item) > 0
                        );
                    })
                    ->action(function (Sale $record, array $data): void {
                        $items = [];
                        foreach ($record->items as $item) {
                            $qty = (int) ($data['qty_'.$item->id] ?? 0);
                            if ($qty > 0) {
                                $items[] = ['sale_item_id' => $item->id, 'quantity' => $qty];
                            }
                        }
                        try {
                            app(\App\Services\Sales\ReturnSaleItemsService::class)->returnItems(
                                $record,
                                $items,
                                (string) $data['destination'],
                                (string) $data['reason'],
                                $record->payment_status === Sale::PAYMENT_PAID
                                    ? [
                                        'method' => (string) ($data['refund_method'] ?? ''),
                                        'amount' => $data['refund_amount'] ?? 0,
                                    ]
                                    : null,
                            );
                        } catch (\DomainException $exception) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['reason' => $exception->getMessage()]);
                        }
                        \Filament\Notifications\Notification::make()->title('Devolução registrada.')->success()->send();
                    }),
                Action::make('exchangeItems')
                    ->label('Trocar itens')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->modalHeading('Troca de produtos')
                    ->fillForm(function (Sale $record): array {
                        $record->load(['items.product', 'returns.items']);
                        $form = [
                            'destination' => \App\Models\SaleReturn::DESTINATION_RESTOCK,
                            'reason' => '',
                            'settlement_method' => 'pix',
                            'settlement_amount' => '0',
                            'outgoing' => [
                                ['product_id' => null, 'quantity' => 1],
                            ],
                        ];
                        foreach ($record->items as $item) {
                            $form['ex_qty_'.$item->id] = 0;
                        }

                        return $form;
                    })
                    ->schema(function (Sale $record): array {
                        $record->load(['items.product', 'returns.items']);
                        $fields = [];
                        foreach ($record->items as $item) {
                            $remaining = $record->remainingQuantityForItem($item);
                            if ($remaining <= 0) {
                                continue;
                            }
                            $fields[] = TextInput::make('ex_qty_'.$item->id)
                                ->label('Devolver: '.($item->product?->name ?? 'Item #'.$item->id).' (restante '.$remaining.')')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($remaining)
                                ->required();
                        }
                        $fields[] = Select::make('destination')
                            ->label('Destino do devolvido')
                            ->options(\App\Models\SaleReturn::DESTINATION_LABELS)
                            ->required();
                        $fields[] = Repeater::make('outgoing')
                            ->label('Produtos de saída')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produto')
                                    ->options(fn () => Product::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Qtd.')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(2);
                        if ($record->payment_status === Sale::PAYMENT_PAID) {
                            $fields[] = Select::make('settlement_method')
                                ->label('Meio do acerto')
                                ->options(SalePayment::METHOD_LABELS)
                                ->required();
                            $fields[] = TextInput::make('settlement_amount')
                                ->label('Valor do acerto (|diferença|; 0 se valores iguais)')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->helperText('Débito dos novos − crédito dos devolvidos. O sistema valida o valor.');
                        }
                        $fields[] = TextInput::make('reason')
                            ->label('Motivo da troca')
                            ->required()
                            ->maxLength(255);

                        return $fields;
                    })
                    ->authorize(function (Sale $record): bool {
                        if (! auth()->user()?->can('return-sales')
                            || $record->status !== 'confirmed'
                            || $record->payment_status === Sale::PAYMENT_REFUNDED
                            || $record->trashed()) {
                            return false;
                        }
                        $record->loadMissing(['items', 'returns.items']);

                        return $record->items->contains(
                            fn ($item): bool => $record->remainingQuantityForItem($item) > 0
                        );
                    })
                    ->action(function (Sale $record, array $data): void {
                        $returnItems = [];
                        foreach ($record->items as $item) {
                            $qty = (int) ($data['ex_qty_'.$item->id] ?? 0);
                            if ($qty > 0) {
                                $returnItems[] = ['sale_item_id' => $item->id, 'quantity' => $qty];
                            }
                        }
                        $outgoing = collect($data['outgoing'] ?? [])
                            ->map(fn (array $row): array => [
                                'product_id' => (int) ($row['product_id'] ?? 0),
                                'quantity' => (int) ($row['quantity'] ?? 0),
                            ])
                            ->all();
                        try {
                            app(\App\Services\Sales\ExchangeSaleItemsService::class)->exchange(
                                $record,
                                $returnItems,
                                $outgoing,
                                (string) $data['destination'],
                                (string) $data['reason'],
                                $record->payment_status === Sale::PAYMENT_PAID
                                    ? [
                                        'method' => (string) ($data['settlement_method'] ?? ''),
                                        'amount' => $data['settlement_amount'] ?? 0,
                                    ]
                                    : null,
                            );
                        } catch (\DomainException $exception) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['reason' => $exception->getMessage()]);
                        }
                        \Filament\Notifications\Notification::make()->title('Troca registrada.')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancelar venda')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([TextInput::make('reason')->label('Motivo do cancelamento')->required()->maxLength(255)])
                    ->authorize(fn (Sale $record): bool => auth()->user()?->can('cancel-sales') && $record->status === 'confirmed' && ! $record->trashed())
                    ->action(function (Sale $record, array $data): void {
                        try {
                            app(\App\Services\Stocks\CancelSaleService::class)->cancel($record, $data['reason']);
                        } catch (\DomainException $exception) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['reason' => $exception->getMessage()]);
                        }
                        \Filament\Notifications\Notification::make()->title('Venda cancelada e estoque devolvido.')->success()->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords(),
                    RestoreBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function canEdit($record): bool
    {
        return parent::canEdit($record) && ! $record->trashed() && $record->status === 'draft';
    }

    public static function canDelete($record): bool
    {
        return parent::canDelete($record) && $record->status === 'draft';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete-sales') ?? false;
    }

    public static function canForceDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return (auth()->user()?->can('edit-sales') ?? false) && $record->status === 'draft';
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('edit-sales') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSales::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
