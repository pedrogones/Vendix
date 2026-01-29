<?php

namespace App\Services\Stocks;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function out(
        Product $product,
        int $quantity,
        string $reason,
        ?Model $reference = null
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantidade inválida');
        }

        DB::transaction(function () use ($product, $quantity, $reason, $reference) {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedProduct) {
                throw new \DomainException('Produto não encontrado.');
            }

            if ($lockedProduct->stock < $quantity) {
                throw new \DomainException(sprintf(
                    'Estoque insuficiente para "%s". Disponível: %d, solicitado: %d.',
                    $lockedProduct->name,
                    $lockedProduct->stock,
                    $quantity
                ));
            }

            $referenceType = $reference ? get_class($reference) : $reason;

            StockMovement::create([
                'product_id'     => $lockedProduct->id,
                'type'           => 'out',
                'quantity'       => $quantity,
                'reason'         => $reason,
                'reference_id'   => $reference?->id,
                'reference_type' => $referenceType,
                'user_id'        => auth()->id(),
            ]);

            $lockedProduct->decrement('stock', $quantity);
        });
    }

    public function in(
        Product $product,
        int $quantity,
        string $reason,
        Model|null $reference = null
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantidade inválida');
        }

        DB::transaction(function () use ($product, $quantity, $reason, $reference) {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedProduct) {
                throw new \DomainException('Produto não encontrado.');
            }

            $referenceType = $reference ? get_class($reference) : $reason;

            StockMovement::create([
                'product_id'     => $lockedProduct->id,
                'type'           => 'in',
                'quantity'       => $quantity,
                'reason'         => $reason,
                'reference_id'   => $reference?->id,
                'reference_type' => $referenceType,
                'user_id'        => auth()->id(),
            ]);

            $lockedProduct->increment('stock', $quantity);
        });
    }
}
