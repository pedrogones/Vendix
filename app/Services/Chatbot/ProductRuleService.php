<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductRuleService
{
    public function findByText(string $text): ?Product
    {
        $tokens = $this->extractTokens($text);

        if ($tokens === []) {
            return null;
        }

        return Product::query()
            ->with('category')
            ->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $query->orWhere('name', 'like', '%' . $token . '%')
                        ->orWhere('description', 'like', '%' . $token . '%')
                        ->orWhere('sku', 'like', '%' . $token . '%');
                }
            })
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN stock > 0 THEN 1 ELSE 0 END DESC')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * @return Collection<int,Product>
     */
    public function promotionalProducts(int $limit = 5): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull('promotional_price')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{unit_price:float,normal_price:float,promotional_price:float|null,is_promotion:bool,economy:float}
     */
    public function pricing(Product $product): array
    {
        $normalPrice = (float) $product->price;
        $promotionalPrice = $product->promotional_price !== null ? (float) $product->promotional_price : null;
        $isPromotion = $promotionalPrice !== null && $promotionalPrice > 0 && $promotionalPrice < $normalPrice;

        return [
            'unit_price' => $isPromotion ? $promotionalPrice : $normalPrice,
            'normal_price' => $normalPrice,
            'promotional_price' => $isPromotion ? $promotionalPrice : null,
            'is_promotion' => $isPromotion,
            'economy' => $isPromotion ? round($normalPrice - $promotionalPrice, 2) : 0.0,
        ];
    }

    /**
     * @return array{ok:bool,message:string,max_allowed:int}
     */
    public function validateForSale(Product $product, int $quantity): array
    {
        if (! $product->is_active) {
            return [
                'ok' => false,
                'message' => sprintf('O produto "%s" esta inativo no momento.', $product->name),
                'max_allowed' => 0,
            ];
        }

        if ($product->stock <= 0) {
            return [
                'ok' => false,
                'message' => sprintf('O produto "%s" esta sem estoque.', $product->name),
                'max_allowed' => 0,
            ];
        }

        if ($quantity > $product->stock) {
            return [
                'ok' => false,
                'message' => sprintf('Estoque insuficiente para "%s". Disponivel: %d unidade(s).', $product->name, $product->stock),
                'max_allowed' => (int) $product->stock,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Produto disponivel.',
            'max_allowed' => (int) $product->stock,
        ];
    }

    public function buildProductSummary(Product $product): string
    {
        $pricing = $this->pricing($product);

        if (! $product->is_active) {
            return sprintf('Encontrei "%s", mas ele esta inativo no momento.', $product->name);
        }

        if ((int) $product->stock <= 0) {
            return sprintf('Encontrei "%s", mas ele esta sem estoque.', $product->name);
        }

        if ($pricing['is_promotion']) {
            return sprintf(
                'Produto: %s. Preco normal: %s. Preco promocional: %s. Economia de %s por unidade. Estoque: %d unidade(s).',
                $product->name,
                $this->formatCurrency($pricing['normal_price']),
                $this->formatCurrency((float) $pricing['promotional_price']),
                $this->formatCurrency($pricing['economy']),
                (int) $product->stock,
            );
        }

        return sprintf(
            'Produto: %s. Preco: %s. Estoque disponivel: %d unidade(s).',
            $product->name,
            $this->formatCurrency($pricing['normal_price']),
            (int) $product->stock,
        );
    }

    public function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * @return array<int,string>
     */
    private function extractTokens(string $text): array
    {
        $normalized = (string) Str::of($text)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];

        return collect($tokens)
            ->filter(fn (string $token): bool => strlen($token) >= 3)
            ->reject(fn (string $token): bool => in_array($token, $this->stopwords(), true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    private function stopwords(): array
    {
        return [
            'qual',
            'quais',
            'quero',
            'produto',
            'produtos',
            'pedido',
            'comprar',
            'adicionar',
            'remover',
            'preco',
            'promocao',
            'promocoes',
            'valor',
            'estoque',
            'item',
            'itens',
        ];
    }
}
