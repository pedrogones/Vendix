<?php

namespace App\Services\Products;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ProductLookupService
{
    public function lookup(string $barcode): ?array
    {
        return $this->lookupBluesoft($barcode);
    }

    protected function lookupBluesoft(string $barcode): ?array
    {
        $baseUrl = rtrim((string) config('services.bluesoft.url'), '/');

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Cosmos-Token' => (string) config('services.bluesoft.token'),
                'User-Agent' => (string) config('services.bluesoft.user_agent'),
                'Accept' => 'application/json',
            ])
            ->get($baseUrl.'/gtins/'.rawurlencode($barcode).'.json');

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->ok()) {
            logger()->error('Bluesoft lookup failed', [
                'barcode' => $barcode,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        $data = $response->json();
        logger($barcode, $data);

        return [
            'name' => Arr::get($data, 'description'),
            'description' => Arr::get($data, 'ncm.full_description'),
            'brand' => Arr::get($data, 'brand.name'),
            'category' => Arr::get($data, 'category.description'),
            'weight' => Arr::get($data, 'package.quantity'),
            'thumbnail' => Arr::get($data, 'thumbnail'),
        ];

    }
}
