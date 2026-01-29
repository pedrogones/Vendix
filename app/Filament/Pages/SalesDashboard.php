<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboard extends Page
{
    protected static ?string $title = 'Dashboard de Vendas';
    protected static ?string $navigationLabel = 'Dashboard de Vendas';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPresentationChartLine;
    protected static string|null|\UnitEnum $navigationGroup = 'Relatórios';

    protected string $view = 'filament.pages.sales-dashboard';

    public array $dashboard = [];

    public function mount(): void
    {
        $this->dashboard = $this->buildDashboard();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-reports') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view-reports') ?? false;
    }

    private function buildDashboard(): array
    {
        $confirmedSales = Sale::query()->where('status', 'confirmed');

        $totalSales = $confirmedSales->count();
        $totalRevenue = (float) $confirmedSales->sum('total');
        $ticketMedio = $totalSales > 0 ? $totalRevenue / $totalSales : 0.0;
        $productsTotal = Product::query()->count();

        [$salesLabels, $salesValues] = $this->salesByDay();
        [$topProductLabels, $topProductValues] = $this->topProducts();
        [$categoryLabels, $categoryValues] = $this->categoryDistribution();

        $lowStockCount = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
        $highStockCount = max($productsTotal - $lowStockCount, 0);

        $lowStockList = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(4)
            ->get(['id', 'name', 'stock', 'min_stock'])
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
            ])
            ->all();

        $highStockList = Product::query()
            ->whereColumn('stock', '>', 'min_stock')
            ->orderByDesc('stock')
            ->limit(4)
            ->get(['id', 'name', 'stock'])
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'stock' => (int) $product->stock,
            ])
            ->all();

        $productInsights = $this->productInsights();
        $favorites = $this->favoriteProducts();

        return [
            'meta' => [
                'updated_at' => Carbon::now()->format('d/m/Y H:i'),
            ],
            'kpis' => [
                'total_sales' => $totalRevenue,
                'ticket' => $ticketMedio,
                'products_total' => $productsTotal,
            ],
            'charts' => [
                'sales' => [
                    'labels' => $salesLabels,
                    'values' => $salesValues,
                ],
                'top_products' => [
                    'labels' => $topProductLabels,
                    'values' => $topProductValues,
                ],
                'categories' => [
                    'labels' => $categoryLabels,
                    'values' => $categoryValues,
                ],
                'stock' => [
                    'labels' => ['Estoque alto', 'Estoque baixo'],
                    'values' => [$highStockCount, $lowStockCount],
                ],
            ],
            'products' => $productInsights,
            'stock' => [
                'low' => $lowStockList,
                'high' => $highStockList,
            ],
            'favorites' => [
                'top' => $favorites,
            ],
        ];
    }

    private function salesByDay(): array
    {
        $start = Carbon::now()->subDays(11)->startOfDay();
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

        for ($i = 0; $i < 12; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d/m');
            $values[] = (float) ($sales[$key] ?? 0);
        }

        return [$labels, $values];
    }

    private function topProducts(): array
    {
        $rows = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('sale', fn ($query) => $query->where('status', 'confirmed'))
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(6)
            ->with('product:id,name')
            ->get();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row->product?->name ?? ('Produto #' . $row->product_id);
            $values[] = (int) $row->qty;
        }

        return [$labels, $values];
    }

    private function categoryDistribution(): array
    {
        $rows = SaleItem::query()
            ->select('products.category_id', DB::raw('SUM(sale_items.total_price) as total'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'confirmed')
            ->groupBy('products.category_id')
            ->orderByDesc('total')
            ->get();

        $categoryMap = Category::query()
            ->whereIn('id', $rows->pluck('category_id')->filter())
            ->pluck('name', 'id');

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row->category_id
                ? ($categoryMap[$row->category_id] ?? 'Sem categoria')
                : 'Sem categoria';
            $values[] = (float) $row->total;
        }

        return [$labels, $values];
    }

    private function productInsights(): array
    {
        $mostSoldRow = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('sale', fn ($query) => $query->where('status', 'confirmed'))
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->with('product:id,name')
            ->first();

        $featuredRow = SaleItem::query()
            ->select('product_id', DB::raw('SUM(total_price) as revenue'))
            ->whereHas('sale', fn ($query) => $query->where('status', 'confirmed'))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->with('product:id,name')
            ->first();

        $mostExpensive = Product::query()->orderByDesc('price')->first();
        $cheapest = Product::query()->orderBy('price')->first();

        return [
            'most_sold' => [
                'name' => $mostSoldRow?->product?->name ?? '—',
                'value' => (int) ($mostSoldRow?->qty ?? 0),
            ],
            'most_expensive' => [
                'name' => $mostExpensive?->name ?? '—',
                'value' => (float) ($mostExpensive?->price ?? 0),
            ],
            'cheapest' => [
                'name' => $cheapest?->name ?? '—',
                'value' => (float) ($cheapest?->price ?? 0),
            ],
            'featured' => [
                'name' => $featuredRow?->product?->name ?? '—',
                'value' => (float) ($featuredRow?->revenue ?? 0),
            ],
        ];
    }

    private function favoriteProducts(): array
    {
        return ProductFavorite::query()
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('product:id,name')
            ->get()
            ->map(fn (ProductFavorite $favorite) => [
                'name' => $favorite->product?->name ?? ('Produto #' . $favorite->product_id),
                'count' => (int) $favorite->total,
            ])
            ->all();
    }
}
