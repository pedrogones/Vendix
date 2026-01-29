<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\User;
use App\Services\Reports\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

class SalesReport extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static ?string $title = 'Relatório de Vendas';
    protected static ?string $navigationLabel = 'Relatório de Vendas';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedDocumentChartBar;
    protected static string|null|\UnitEnum $navigationGroup = 'Relatórios';
    protected static ?string $slug = 'relatorio-vendas';

    protected string $view = 'filament.pages.sales-report';

    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'status' => 'confirmed',
        'user_id' => null,
        'client_id' => null,
        'min_total' => null,
        'max_total' => null,
    ];

    public int $perPage = 10;

    public function mount(): void
    {
        $this->form->fill($this->filters);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-reports') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view-reports') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('start_date')
                    ->label('Data inicial')
                    ->native(false),
                DatePicker::make('end_date')
                    ->label('Data final')
                    ->native(false),
                Select::make('status')
                    ->label('Status')
                    ->options(SalesReportService::STATUS_LABELS)
                    ->placeholder('Todos')
                    ->nullable(),
                Select::make('user_id')
                    ->label('Vendedor')
                    ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos'),
                Select::make('client_id')
                    ->label('Cliente')
                    ->options(Client::query()->orderBy('cpf')->pluck('cpf', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos'),
                TextInput::make('min_total')
                    ->label('Total mínimo')
                    ->numeric()
                    ->prefix('R$'),
                TextInput::make('max_total')
                    ->label('Total máximo')
                    ->numeric()
                    ->prefix('R$'),
            ])
            ->columns(3)
            ->statePath('filters');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Imprimir PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->visible(fn () => auth()->user()?->can('export-reports') ?? false)
                ->action('downloadPdf'),
        ];
    }

    public function applyFilters(): void
    {
        $this->form->validate();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'start_date' => null,
            'end_date' => null,
            'status' => 'confirmed',
            'user_id' => null,
            'client_id' => null,
            'min_total' => null,
            'max_total' => null,
        ];

        $this->form->fill($this->filters);
        $this->resetPage();
    }

    public function downloadPdf()
    {
        $report = app(SalesReportService::class)->build($this->filters, 10000);
        $company = config('app.company');

        $pdf = Pdf::loadView('reports.sales-report', [
            'report' => $report,
            'company' => $company,
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'relatorio-vendas-' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function buildReport(): array
    {
        return app(SalesReportService::class)->build($this->filters, $this->perPage);
    }

    protected function getViewData(): array
    {
        return [
            'report' => $this->buildReport(),
        ];
    }
}
