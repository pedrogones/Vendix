<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\Sales\ChatOrderLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function show(): View
    {
        return view('portal.order-status', [
            'sale' => null,
            'error' => null,
        ]);
    }

    public function lookup(Request $request, ChatOrderLookupService $lookup): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $sale = $lookup->findByOrderAndPhone($data['order_number'], $data['phone']);

        return view('portal.order-status', [
            'sale' => $sale,
            'error' => $sale ? null : 'Não encontramos um pedido com esses dados.',
            'order_number' => $data['order_number'],
            'phone' => $data['phone'],
        ]);
    }

    public function byToken(string $token, ChatOrderLookupService $lookup): View
    {
        $sale = $lookup->findByToken($token);

        return view('portal.order-status', [
            'sale' => $sale,
            'error' => $sale ? null : 'Link de acompanhamento inválido ou expirado.',
        ]);
    }

    public static function maskAddress(?string $address): string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return '—';
        }
        if (mb_strlen($address) <= 12) {
            return mb_substr($address, 0, 3).'…';
        }

        return mb_substr($address, 0, 10).'…'.mb_substr($address, -6);
    }
}
