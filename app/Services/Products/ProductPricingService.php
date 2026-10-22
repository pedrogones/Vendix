<?php

namespace App\Services\Products;

use App\Models\Product;

class ProductPricingService
{
    public function isPromotion(Product $product): bool
    {
        return $product->is_on_sale && $product->promotional_price !== null
            && $this->cents($product->promotional_price) > 0
            && $this->cents($product->promotional_price) < $this->cents($product->price);
    }

    public function price(Product $product): float
    {
        return $this->cents($this->isPromotion($product) ? $product->promotional_price : $product->price) / 100;
    }

    public function cents(int|float|string|null $value): int
    {
        return (int) round((float) $value * 100);
    }

    public function lineTotal(int $quantity, float|string $unitPrice, float|string|null $discount = 0): float
    {
        $gross = $quantity * $this->cents($unitPrice);
        $discountCents = $this->cents($discount);
        if ($quantity <= 0 || $this->cents($unitPrice) < 0 || $discountCents < 0 || $discountCents > $gross) {
            throw new \DomainException('Quantidade, preço ou desconto inválido para o item.');
        }

        return ($gross - $discountCents) / 100;
    }
}
