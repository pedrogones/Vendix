<?php

namespace App\Filament\Pages;

use App\Filament\Pages\SalesReport;
use App\Filament\Pages\StartSale;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ControlPanel extends Page
{
    protected static bool $isDiscovered = false;
    protected static string $routePath = '/';

    protected static ?string $title = 'Painel de Controle';
    protected static ?string $navigationLabel = 'Painel de Controle';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedHome;
    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.control-panel';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-dashboard') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view-dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->buildData(),
            'urls' => [
                'start_sale' => StartSale::getUrl(),
                'products' => ProductResource::getUrl('index'),
                'stock' => StockMovementResource::getUrl('index'),
                'report' => SalesReport::getUrl(),
            ],
        ];
    }

    private function buildData(): array
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $todaySalesQuery = Sale::query()
            ->where('status', 'confirmed')
            ->whereBetween('created_at', [$todayStart, $todayEnd]);

        $todayTotal = (float) $todaySalesQuery->sum('total');
        $todayCount = (int) $todaySalesQuery->count();
        $todayTicket = $todayCount > 0 ? $todayTotal / $todayCount : 0.0;

        $monthTotal = (float) Sale::query()
            ->where('status', 'confirmed')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('total');

        $productsTotal = (int) Product::query()->count();
        $lowStockCount = (int) Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $lowStock = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(5)
            ->get(['id', 'name', 'stock', 'min_stock'])
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
            ])
            ->all();

        $recentSales = Sale::query()
            ->with(['client', 'user'])
            ->where('status', 'confirmed')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'total' => (float) $sale->total,
                'created_at' => $sale->created_at?->format('d/m H:i'),
                'client' => $sale->client?->cpf ?? 'Sem CPF',
                'user' => $sale->user?->name ?? '—',
            ])
            ->all();

        [$trendLabels, $trendValues] = $this->salesTrend();

        return [
            'kpis' => [
                'today_total' => $todayTotal,
                'today_ticket' => $todayTicket,
                'month_total' => $monthTotal,
                'products_total' => $productsTotal,
                'low_stock' => $lowStockCount,
            ],
            'trend' => [
                'labels' => $trendLabels,
                'values' => $trendValues,
            ],
            'low_stock' => $lowStock,
            'recent_sales' => $recentSales,
            'meta' => [
                'updated_at' => Carbon::now()->format('d/m/Y H:i'),
            ],
        ];
    }

    private function salesTrend(): array
    {
        $start = Carbon::now()->subDays(6)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $sales = Sale::query()
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('status', 'confirmed')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->map(fn ($value) => (float) $value);

        $labels = [];
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d/m');
            $values[] = (float) ($sales[$key] ?? 0);
        }

        return [$labels, $values];
    }
}
