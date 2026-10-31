<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ChatOrders extends Page
{
    protected static ?string $title = 'Pedidos do chat';

    protected static ?string $navigationLabel = 'Pedidos do chat';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|null|\UnitEnum $navigationGroup = 'Vendas';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pedidos-chat';

    protected string $view = 'filament.pages.chat-orders';

    public string $filter = 'open';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-chat-orders') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return list<Sale>
     */
    public function getOrdersProperty(): array
    {
        $query = Sale::query()
            ->with(['items.product'])
            ->where('origin', 'chatbot')
            ->where('status', 'confirmed')
            ->orderByDesc('needs_human_help')
            ->orderByDesc('id');

        if ($this->filter === 'help') {
            $query->where('needs_human_help', true);
        } elseif ($this->filter === 'open') {
            $query->whereNotIn('fulfillment_status', [Sale::FULFILLMENT_COMPLETED, Sale::FULFILLMENT_CANCELED]);
        }

        return $query->limit(50)->get()->all();
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['open', 'help', 'all'], true)) {
            return;
        }
        $this->filter = $filter;
    }

    public function advanceStatus(int $saleId): void
    {
        $this->authorizeManage();
        $sale = $this->findChatSale($saleId);
        $next = match ($sale->fulfillment_status) {
            Sale::FULFILLMENT_RECEIVED => Sale::FULFILLMENT_PREPARING,
            Sale::FULFILLMENT_PREPARING => Sale::FULFILLMENT_READY,
            Sale::FULFILLMENT_READY => Sale::FULFILLMENT_COMPLETED,
            default => null,
        };
        if ($next === null) {
            Notification::make()->title('Este pedido não pode avançar a partir do status atual.')->warning()->send();

            return;
        }
        $sale->updateFulfillment(['fulfillment_status' => $next]);
        Notification::make()->title('Status atualizado para '.Sale::FULFILLMENT_STATUS_LABELS[$next].'.')->success()->send();
    }

    public function cancelFulfillment(int $saleId): void
    {
        $this->authorizeManage();
        $sale = $this->findChatSale($saleId);
        $sale->updateFulfillment(['fulfillment_status' => Sale::FULFILLMENT_CANCELED]);
        Notification::make()->title('Atendimento do pedido cancelado.')->success()->send();
    }

    public function clearHumanHelp(int $saleId): void
    {
        $this->authorizeManage();
        $sale = $this->findChatSale($saleId);
        $sale->updateFulfillment([
            'needs_human_help' => false,
            'human_help_note' => null,
        ]);
        Notification::make()->title('Pedido removido da fila de atendimento humano.')->success()->send();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-chat-orders'), 403);
    }

    private function findChatSale(int $saleId): Sale
    {
        return Sale::query()
            ->whereKey($saleId)
            ->where('origin', 'chatbot')
            ->where('status', 'confirmed')
            ->firstOrFail();
    }
}
