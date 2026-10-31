<?php

namespace App\Services\Sales;

use App\Models\Sale;

class ChatOrderLookupService
{
    public function findByOrderAndPhone(string $orderNumber, string $phone): ?Sale
    {
        $id = $this->parseOrderId($orderNumber);
        $normalizedPhone = Sale::normalizePhone($phone);
        if ($id === null || strlen($normalizedPhone) < 10) {
            return null;
        }

        $sale = Sale::query()
            ->with(['items.product'])
            ->whereKey($id)
            ->where('origin', 'chatbot')
            ->where('status', 'confirmed')
            ->first();

        if (! $sale || Sale::normalizePhone((string) $sale->buyer_phone) !== $normalizedPhone) {
            return null;
        }

        return $sale;
    }

    public function findByToken(string $token): ?Sale
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 16) {
            return null;
        }

        return Sale::query()
            ->with(['items.product'])
            ->where('public_token', $token)
            ->where('origin', 'chatbot')
            ->where('status', 'confirmed')
            ->first();
    }

    private function parseOrderId(string $orderNumber): ?int
    {
        $digits = preg_replace('/\D+/', '', $orderNumber) ?? '';
        if ($digits === '') {
            return null;
        }

        return (int) $digits;
    }
}
