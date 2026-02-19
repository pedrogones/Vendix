<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class VitrineController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['image', 'category'])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        $promotionalProducts = Product::query()
            ->with('image')
            ->where('is_active', true)
            ->whereNotNull('promotional_price')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $categories = Category::query()
            ->where('status', true)
            ->whereHas('products', function ($query): void {
                $query->where('is_active', true);
            })
            ->withCount([
                'products as active_products_count' => function ($query): void {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $company = [
            'name' => env('COMPANY_NAME', config('app.name', 'Vendix')),
            'cnpj' => env('COMPANY_CNPJ', 'CNPJ nao informado'),
            'phone' => env('COMPANY_PHONE', 'Telefone nao informado'),
            'address' => env('COMPANY_ADDRESS', 'Endereco nao informado'),
        ];

        return view('vitrine.index', [
            'categories' => $categories,
            'company' => $company,
            'products' => $products,
            'promotionalProducts' => $promotionalProducts,
        ]);
    }
}
