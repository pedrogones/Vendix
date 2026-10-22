<?php

namespace App\Services\Stocks;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockService
{
    public function out(Product $product, int $quantity, string $reason, ?Model $reference = null, ?string $description = null): StockMovement
    {
        return $this->move($product, $quantity, 'out', $reason, $reference, $description);
    }

    public function in(Product $product, int $quantity, string $reason, ?Model $reference = null, ?string $description = null): StockMovement
    {
        return $this->move($product, $quantity, 'in', $reason, $reference, $description);
    }

    public function count(Product $product, int $balance, string $description): ?StockMovement
    {
        Gate::authorize('create-stock-movements');
        if ($balance < 0 || (trim($description) === '' || mb_strlen($description) > 255)) {
            throw new \DomainException('Informe saldo não negativo e motivo para o ajuste.');
        }

        return DB::transaction(function () use ($product, $balance, $description): ?StockMovement {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $difference = $balance - (int) $locked->stock;
            if ($difference === 0) {
                return null;
            }

            return $this->move($locked, abs($difference), $difference > 0 ? 'in' : 'out', 'manual', null, $description);
        });
    }

    public function manual(Product $product, int $quantity, string $type, string $reason, string $description): StockMovement
    {
        Gate::authorize('create-stock-movements');
        if (! in_array($reason, ['purchase', 'loss', 'manual', 'return'], true) || (trim($description) === '' || mb_strlen($description) > 255)) {
            throw new \DomainException('Informe um motivo válido e a justificativa da movimentação.');
        }

        return $this->move($product, $quantity, $type, $reason, null, $description);
    }

    public function compensate(StockMovement $movement, string $description): StockMovement
    {
        Gate::authorize('create-stock-movements');
        if ((trim($description) === '' || mb_strlen($description) > 255)) {
            throw new \DomainException('Informe o motivo da correção.');
        }

        return DB::transaction(function () use ($movement, $description): StockMovement {
            // Product first: same order as regular stock writes.
            $product = Product::query()->lockForUpdate()->findOrFail($movement->product_id);
            $original = StockMovement::query()->lockForUpdate()->findOrFail($movement->id);
            if ($original->reference_type !== 'manual' || $original->compensates_id !== null
                || ! in_array($original->type, ['in', 'out'], true)
                || StockMovement::query()->where('compensates_id', $original->id)->exists()) {
                throw new \DomainException('Apenas movimentos manuais ainda não corrigidos podem ser compensados. Vendas devem ser canceladas pela venda.');
            }

            return $this->move($product, (int) $original->quantity, $original->type === 'in' ? 'out' : 'in', 'manual', null, $description, $original->id);
        });
    }

    private function move(Product $product, int $quantity, string $type, string $reason, ?Model $reference, ?string $description, ?int $compensatesId = null): StockMovement
    {
        if ($quantity <= 0 || ! in_array($type, ['in', 'out'], true)
            || ! in_array($reason, ['sale', 'purchase', 'loss', 'manual', 'return'], true)) {
            throw new \DomainException('Quantidade, tipo ou motivo inválido.');
        }

        return DB::transaction(function () use ($product, $quantity, $type, $reason, $reference, $description, $compensatesId): StockMovement {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            if ($type === 'out' && $locked->stock < $quantity) {
                throw new \DomainException(sprintf('Estoque insuficiente para "%s". Disponível: %d, solicitado: %d.', $locked->name, $locked->stock, $quantity));
            }

            $movement = StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_id' => $reference?->getKey(),
                'reference_type' => $reference ? get_class($reference) : 'manual',
                'reference' => $description,
                'user_id' => auth()->id(),
                'compensates_id' => $compensatesId,
            ]);

            // Query write is deliberately confined to this service; normal model saves reject direct stock edits.
            Product::query()->whereKey($locked->id)->increment('stock', $type === 'in' ? $quantity : -$quantity);

            return $movement;
        });
    }
}
