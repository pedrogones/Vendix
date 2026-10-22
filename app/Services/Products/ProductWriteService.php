<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Services\Stocks\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductWriteService
{
    public function save(array $data, ?Product $product = null): Product
    {
        Gate::authorize($product ? 'edit-products' : 'create-products');

        return DB::transaction(function () use ($data, $product): Product {
            $product = $product ? Product::query()->lockForUpdate()->findOrFail($product->id) : new Product;
            $validated = Validator::make($data, [
                'price' => ['required', 'numeric', 'min:0'],
                'promotional_price' => ['nullable', 'numeric', 'gt:0', 'lt:price'],
                'stock' => ['sometimes', 'integer', 'min:0'],
                'min_stock' => ['sometimes', 'integer', 'min:0'],
                'stock_reason' => ['nullable', 'string', 'max:255'],
            ])->validate();
            if (($data['is_on_sale'] ?? false) && ! isset($validated['promotional_price'])) {
                throw ValidationException::withMessages(['promotional_price' => 'Informe um preço promocional válido.']);
            }
            $balance = (int) ($data['stock'] ?? $product->stock ?? 0);
            $isNew = ! $product->exists;
            $currentStock = (int) ($product->stock ?? 0);
            $reason = trim((string) ($data['stock_reason'] ?? ''));
            if (! $isNew && $balance !== $currentStock && $reason === '') {
                throw ValidationException::withMessages(['stock_reason' => 'Informe o motivo da alteração do saldo.']);
            }
            if ($balance !== $currentStock) {
                Gate::authorize('create-stock-movements');
            }
            unset($data['stock'], $data['stock_reason']);
            $product->fill($data);
            if ($isNew) {
                $product->stock = 0;
            }
            $product->save();
            if ($balance !== $currentStock) {
                app(StockService::class)->count($product, $balance, $isNew ? 'Saldo inicial no cadastro' : $reason);
            }

            return $product->refresh();
        });
    }
}
