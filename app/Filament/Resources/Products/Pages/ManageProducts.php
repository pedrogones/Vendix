<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Archives\ArchiveUploadService;
use App\Services\Archives\ImageFromUrlService;
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

                            while (\App\Models\Product::where('slug', $slug)->exists()) {
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
                        $data['image_file'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
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
            $type = $this->detectBarcodeType($code);

            match ($type) {
                'ean13', 'ean8' => $this->handleProductCode($code),

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

    protected function handleProductCode(string $code): void
    {
        $product = Product::where('barcode', $code)->first();

        if ($product) {
            Notification::make()
                ->title('Produto encontrado')
                ->body($product->name)
                ->success()
                ->send();

            $this->unmountAction();

            $this->mountAction('editFromScanner', arguments: [
                'product_id' => $product->id,
            ]);

            return;
        }

        $lookup = app(ProductLookupService::class)->lookup($code);

        if (! $lookup) {
            Notification::make()
                ->title('Produto não encontrado')
                ->body("Código: {$code}")
                ->warning()
                ->send();

            $this->dispatch('qr:result', found: false, message: 'Produto não encontrado, tente novamente ou o cadastre manualmente.');
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
            'description' => $lookup['description'],
            'name'          => $lookup['name'] ?? null,
            'category_id'   => $category?->id,
            'external_data' => $lookup,
        ]);


    }

    private function detectBarcodeType(string $code): string
    {
        $code = trim($code);

        // QR com URL (promoção, site, iFood, etc)
        if (filter_var($code, FILTER_VALIDATE_URL)) {
            return 'qr_url';
        }

        // EAN padrão de produto
        if (preg_match('/^\d{13}$/', $code)) {
            return 'ean13';
        }

        if (preg_match('/^\d{8}$/', $code)) {
            return 'ean8';
        }

        // Código interno (balança, caixa, etiqueta)
        if (preg_match('/^\d{4,14}$/', $code)) {
            return 'internal_numeric';
        }

        // QR ou código alfanumérico
        if (preg_match('/^[\w\-.:\/]+$/', $code)) {
            return 'qr_text';
        }

        return 'unknown';
    }


    protected function handleUrl(string $url): void
    {
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
        Notification::make()
            ->title('Código interno identificado')
            ->body("Código: {$code}")
            ->info()
            ->send();
    }

}
