<?php

namespace App\Services\Products;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class ProductLookupService
{
    public function lookup(string $barcode): ?array
    {
        return $this->lookupBluesoft($barcode);
    }

    protected function lookupBluesoft(string $barcode): ?array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'X-Cosmos-Token' => env('BLUESOFT_API_TOKEN'),
                'Accept'        => 'application/json',
            ])
            ->get("https://api.cosmos.bluesoft.com.br/gtins/{$barcode}");

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->ok()) {
            logger()->error('Bluesoft lookup failed', [
                'barcode' => $barcode,
                'status'  => $response->status(),
                'body'    => $response->json(),
            ]);
            return null;
        }

        $data = $response->json();
        logger($barcode, $data);

        return [
            'name'          => Arr::get($data, 'description'),
            'description'   => Arr::get($data, 'ncm.full_description'),
            'brand'         => Arr::get($data, 'brand.name'),
            'category'      => Arr::get($data, 'category.description'),
            'weight'        => Arr::get($data, 'package.quantity'),
            'thumbnail'     => Arr::get($data, 'thumbnail'),
        ];

    }

}
