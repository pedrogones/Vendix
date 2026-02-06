<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Archives\ArchiveUploadService;
use App\Services\Products\ProductLookupService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected ?Product $editingProduct = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create')
                ->label('Novo produto')
                ->modalHeading('Cadastrar produto')
                ->form(fn (Schema $schema) => ProductResource::form($schema))
                ->mountUsing(function (Schema $schema, array $arguments) {
                    if (! empty($arguments)) {
                        if (
                            filled($arguments['name'] ?? null) &&
                            empty($arguments['slug'] ?? null)
                        ) {
                            $baseSlug = Str::slug($arguments['name']);
                            $slug = $baseSlug;
                            $i = 1;

                            while (Product::where('slug', $slug)->exists()) {
                                $slug = "{$baseSlug}-{$i}";
                                $i++;
                            }

                            $arguments['slug'] = $slug;
                        }

                        $schema->fill($arguments);
                    }
                })
                ->using(function (array $data) {
                    if (
                        ! empty($data['image_file']) &&
                        $data['image_file'] instanceof TemporaryUploadedFile
                    ) {
                        $archive = app(ArchiveUploadService::class)->upload(
                            file: $data['image_file'],
                            type: 'image',
                            category: 'product',
                            visibility: 'public',
                        );

                        $data['image_id'] = $archive->id;
                    }
                    unset($data['image_file']);

                    return Product::create($data);
                }),

            Action::make('scan')
                ->label('Escanear código')
                ->icon('heroicon-o-qr-code')
                ->modalHeading('Escanear produto')
                ->modalContent(view('filament.products.scan-product'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fechar'),

            Action::make('editFromScanner')
                ->label('Editar produto')
                ->modalHeading('Editar produto')
                ->modalSubmitActionLabel('Salvar')
                ->form(fn (Schema $schema) => ProductResource::form($schema))
                ->mountUsing(function (Schema $schema, array $arguments) {
                    $this->editingProduct = Product::find($arguments['product_id'] ?? null);

                    if (! $this->editingProduct) {
                        Notification::make()
                            ->title('Produto não encontrado para edição')
                            ->warning()
                            ->send();

                        $this->unmountAction();
                        return;
                    }

                    $data = $this->editingProduct->toArray();
                    if ($this->editingProduct->image?->path) {
                        $data['image_file'] = $this->editingProduct->image->path;
                    }
                    $schema->fill($data);
                })
                ->action(function (array $data) {
                    if (! $this->editingProduct) {
                        return;
                    }
                    if (
                        ! empty($data['image_file']) &&
                        $data['image_file'] instanceof TemporaryUploadedFile
                    ) {
                        $archive = app(ArchiveUploadService::class)->upload(
                            file: $data['image_file'],
                            type: 'image',
                            category: 'product',
                            visibility: 'public',
                        );

                        $data['image_id'] = $archive->id;
                    }

                    unset($data['image_file']);

                    $this->editingProduct->update($data);

                    Notification::make()
                        ->title('Produto atualizado')
                        ->success()
                        ->send();
                }),
        ];
    }

    #[On('barcodeScanned')]
    public function barcodeScanned(string $code): void
    {
        try {
            $code = $this->normalizeCode($code);

            $payload = $this->parseStructuredPayload($code);

            if ($payload && ! empty($payload['url'])) {
                $code = $payload['url'];
            }

            $urlCode = $this->extractCodeFromUrl($code);
            if ($urlCode) {
                $code = $urlCode;
            }

            if ($payload && ! empty($payload['code'])) {
                $code = $this->normalizeCode($payload['code']);
            }

            $type = $this->detectBarcodeType($code);

            match ($type) {
                'gtin' => $this->handleProductCode($code, $payload),
                'internal_numeric' => $this->handleInternalCode($code),
                'qr_url' => $this->handleUrl($code),
                'qr_text' => $this->handleQrText($code),
                default => $this->handleInvalidCode($code),
            };

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }

    protected function handleProductCode(string $code, ?array $payload = null): void
    {
        $product = $this->findProductByAnyCode($code);

        if ($product) {
            Notification::make()
                ->title('Produto encontrado')
                ->body($product->name)
                ->success()
                ->send();

            $this->dispatch('qr:result', found: true, message: 'Produto encontrado.');

            $this->unmountAction();

            $this->mountAction('editFromScanner', arguments: [
                'product_id' => $product->id,
            ]);

            return;
        }

        $prefill = $this->payloadToPrefill($payload, $code);
        if (! empty($prefill)) {
            $this->unmountAction();
            $this->mountAction('create', arguments: $prefill);

            $this->dispatch('qr:result', found: true, message: 'Dados carregados do QR Code.');

            return;
        }

        $lookup = app(ProductLookupService::class)->lookup($code);

        if (! $lookup) {
            Notification::make()
                ->title('Produto não encontrado')
                ->body("Código: {$code}")
                ->warning()
                ->send();

            $this->dispatch('qr:result', found: false, message: 'Produto não encontrado. Tente novamente ou cadastre manualmente.');
            return;
        }

        Notification::make()
            ->title('Produto encontrado')
            ->body('Confira os dados antes de salvar')
            ->info()
            ->send();

        $this->unmountAction();

        $category = null;

        if (! empty($lookup['category'])) {
            $category = app(\App\Services\Categories\ResolveCategoryService::class)
                ->resolve($lookup['category']);
        }

        $this->mountAction('create', arguments: [
            'barcode'       => $code,
            'description'   => $lookup['description'],
            'name'          => $lookup['name'] ?? null,
            'category_id'   => $category?->id,
            'external_data' => $lookup,
        ]);
    }

    private function detectBarcodeType(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return 'unknown';
        }

        if (filter_var($code, FILTER_VALIDATE_URL)) {
            return 'qr_url';
        }

        if ($this->isValidGtin($code)) {
            return 'gtin';
        }

        if (preg_match('/^\d{4,14}$/', $code)) {
            return 'internal_numeric';
        }

        if (preg_match('/^[\w\-.:\/]+$/u', $code)) {
            return 'qr_text';
        }

        return 'unknown';
    }

    private function normalizeCode(string $code): string
    {
        $code = trim($code);
        $code = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $code);
        $code = preg_replace('/\s+/', '', $code);

        if (! str_contains($code, '://')) {
            $code = str_replace(['-', "\u{00A0}"], '', $code);
        }

        return $code;
    }

    private function parseStructuredPayload(string $payload): ?array
    {
        $payload = trim($payload);

        if ($payload === '') {
            return null;
        }

        if (str_starts_with($payload, '{') || str_starts_with($payload, '[')) {
            $data = json_decode($payload, true);

            if (is_array($data)) {
                $code = $data['barcode'] ?? $data['gtin'] ?? $data['ean'] ?? $data['code'] ?? $data['sku'] ?? $data['id'] ?? null;

                return [
                    'code' => $code,
                    'name' => $data['name'] ?? $data['product_name'] ?? null,
                    'description' => $data['description'] ?? $data['descricao'] ?? null,
                    'price' => $data['price'] ?? $data['valor'] ?? null,
                    'category' => $data['category'] ?? $data['categoria'] ?? null,
                    'raw' => $data,
                ];
            }
        }

        if (preg_match('/\b(gtin|ean|barcode|codigo|code|sku|product_id|id)\s*[:=]\s*([A-Za-z0-9\-_.]+)/i', $payload, $matches)) {
            return [
                'code' => $matches[2],
                'raw' => $payload,
            ];
        }

        return null;
    }

    private function extractCodeFromUrl(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $query = [];
        parse_str($parts['query'] ?? '', $query);

        foreach (['gtin', 'ean', 'barcode', 'code', 'sku', 'id'] as $key) {
            if (! empty($query[$key])) {
                return $this->normalizeCode($query[$key]);
            }
        }

        if (! empty($parts['path']) && preg_match('/\d{8,14}/', $parts['path'], $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function payloadToPrefill(?array $payload, string $code): array
    {
        if (empty($payload)) {
            return [];
        }

        $data = [];
        $hasDetails = false;

        if (! empty($payload['name'])) {
            $data['name'] = $payload['name'];
            $hasDetails = true;
        }

        if (! empty($payload['description'])) {
            $data['description'] = $payload['description'];
            $hasDetails = true;
        }

        if (isset($payload['price']) && is_numeric($payload['price'])) {
            $data['price'] = $payload['price'];
            $hasDetails = true;
        }

        if (! empty($payload['category'])) {
            $category = app(\App\Services\Categories\ResolveCategoryService::class)
                ->resolve($payload['category']);

            if ($category) {
                $data['category_id'] = $category->id;
                $hasDetails = true;
            }
        }

        if (! $hasDetails) {
            return [];
        }

        $data['barcode'] = $code;
        $data['external_data'] = $payload['raw'] ?? $payload;

        return $data;
    }

    private function isValidGtin(string $code): bool
    {
        if (! preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $code)) {
            return false;
        }

        $digits = array_map('intval', str_split($code));
        $checkDigit = array_pop($digits);

        $sum = 0;
        $weight = 3;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $sum += $digits[$i] * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        $calculated = (10 - ($sum % 10)) % 10;

        return $checkDigit === $calculated;
    }

    private function findProductByAnyCode(string $code): ?Product
    {
        $candidates = $this->normalizeGtinCandidates($code);

        foreach ($candidates as $candidate) {
            $product = Product::where('barcode', $candidate)->first();
            if ($product) {
                return $product;
            }
        }

        $product = Product::where('sku', $code)->first();
        if ($product) {
            return $product;
        }

        if (ctype_digit($code)) {
            $product = Product::find($code);
            if ($product) {
                return $product;
            }
        }

        return null;
    }

    private function normalizeGtinCandidates(string $code): array
    {
        if (! ctype_digit($code)) {
            return [$code];
        }

        $candidates = [$code];

        $trimmed = ltrim($code, '0');
        if ($trimmed !== '' && $trimmed !== $code) {
            $candidates[] = $trimmed;
        }

        if (strlen($code) === 12) {
            $candidates[] = '0' . $code;
        }

        if (strlen($code) === 13) {
            $candidates[] = str_pad($code, 14, '0', STR_PAD_LEFT);
        }

        if (strlen($code) === 8) {
            $candidates[] = str_pad($code, 13, '0', STR_PAD_LEFT);
        }

        return array_values(array_unique($candidates));
    }

    protected function handleUrl(string $url): void
    {
        $urlCode = $this->extractCodeFromUrl($url);

        if ($urlCode) {
            $this->handleProductCode($urlCode);
            return;
        }

        if (! str_starts_with($url, ['https://', 'http://'])) {
            Notification::make()
                ->title('Link inválido')
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('QR Code contém um link')
            ->actions([
                Action::make('open')
                    ->label('Abrir link')
                    ->url($url, true),
            ])
            ->send();
    }

    protected function handleQrText(string $text): void
    {
        Notification::make()
            ->title('QR Code identificado')
            ->body($text)
            ->info()
            ->send();
    }

    protected function handleInvalidCode(string $code): void
    {
        Notification::make()
            ->title('Código não reconhecido')
            ->body($code)
            ->danger()
            ->send();
    }

    protected function handleInternalCode(string $code): void
    {
        $product = $this->findProductByAnyCode($code);

        if ($product) {
            Notification::make()
                ->title('Produto encontrado')
                ->body($product->name)
                ->success()
                ->send();

            $this->dispatch('qr:result', found: true, message: 'Produto encontrado.');

            $this->unmountAction();

            $this->mountAction('editFromScanner', arguments: [
                'product_id' => $product->id,
            ]);

            return;
        }

        Notification::make()
            ->title('Código interno identificado')
            ->body("Código: {$code}")
            ->info()
            ->send();

        $this->dispatch('qr:result', found: false, message: 'Código interno não encontrado.');
    }
}