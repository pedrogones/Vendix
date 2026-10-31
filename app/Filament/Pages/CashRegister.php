<?php

namespace App\Filament\Pages;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Services\Cash\CashSessionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CashRegister extends Page
{
    protected static ?string $title = 'Caixa';

    protected static ?string $navigationLabel = 'Caixa';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|null|\UnitEnum $navigationGroup = 'Vendas';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'caixa';

    protected string $view = 'filament.pages.cash-register';

    public ?CashSession $session = null;

    public array $summary = [];

    public string $openingAmount = '0';

    public string $openingNotes = '';

    public string $movementType = CashMovement::TYPE_OUT;

    public string $movementAmount = '';

    public string $movementReason = '';

    public string $countedCash = '';

    public string $closingNotes = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-cash-register') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->refreshSession();
    }

    public function refreshSession(): void
    {
        $this->session = app(CashSessionService::class)->currentOpen();
        if ($this->session) {
            $this->session->load(['movements' => fn ($q) => $q->orderByDesc('id'), 'opener']);
            $this->summary = app(CashSessionService::class)->summary($this->session);
            $this->countedCash = number_format($this->summary['expected_cash'], 2, '.', '');
        } else {
            $this->summary = [];
            $recent = CashSession::query()
                ->where('status', CashSession::STATUS_CLOSED)
                ->orderByDesc('closed_at')
                ->with(['opener', 'closer'])
                ->limit(5)
                ->get();
            $this->summary = ['recent' => $recent];
        }
    }

    public function openCash(): void
    {
        try {
            app(CashSessionService::class)->open($this->openingAmount, $this->openingNotes);
            $this->openingAmount = '0';
            $this->openingNotes = '';
            $this->refreshSession();
            Notification::make()->title('Caixa aberto.')->success()->send();
        } catch (\DomainException|\Illuminate\Auth\Access\AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function addMovement(): void
    {
        if (! $this->session) {
            return;
        }

        try {
            app(CashSessionService::class)->addMovement(
                $this->session,
                $this->movementType,
                $this->movementAmount,
                $this->movementReason,
            );
            $this->movementAmount = '';
            $this->movementReason = '';
            $this->refreshSession();
            Notification::make()->title('Movimento registrado.')->success()->send();
        } catch (\DomainException|\Illuminate\Auth\Access\AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function closeCash(): void
    {
        if (! $this->session) {
            return;
        }

        try {
            $closed = app(CashSessionService::class)->close($this->session, $this->countedCash, $this->closingNotes);
            $difference = (float) $closed->difference;
            $message = abs($difference) < 0.005
                ? 'Caixa fechado sem divergência.'
                : 'Caixa fechado. Divergência: R$ '.number_format($difference, 2, ',', '.');
            $this->closingNotes = '';
            $this->refreshSession();
            Notification::make()->title($message)->success()->send();
        } catch (\DomainException|\Illuminate\Auth\Access\AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
