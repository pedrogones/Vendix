<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Sales\Pages\ManageSales;
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
                TextColumn::make('client.cpf')
                    ->label('CPF Cliente')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->searchable(),

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
