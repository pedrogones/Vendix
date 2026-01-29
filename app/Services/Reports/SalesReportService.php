<?php

namespace App\Services\Reports;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesReportService
{
    public const STATUS_LABELS = [
        'draft' => 'Rascunho',
        'confirmed' => 'Confirmada',
        'canceled' => 'Cancelada',
    ];

    public function build(array $filters, int $perPage = 15): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = Sale::query();

        $this->applyFilters($query, $filters);

        $totalSales = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total');

        $itemsQuery = SaleItem::query()
            ->whereHas('sale', function (Builder $saleQuery) use ($filters) {
                $this->applyFilters($saleQuery, $filters);
            });

        $totalItems = (int) $itemsQuery->sum('quantity');
        $totalDiscount = (float) $itemsQuery->sum('discount');
        $ticketMedio = $totalSales > 0 ? $totalRevenue / $totalSales : 0.0;

        $sales = $query
            ->with(['client', 'user', 'items.product'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'filters' => $filters,
            'sales' => $sales,
            'totals' => [
                'sales' => $totalSales,
                'items' => $totalItems,
                'discount' => $totalDiscount,
                'revenue' => $totalRevenue,
                'ticket_medio' => $ticketMedio,
            ],
            'status_labels' => self::STATUS_LABELS,
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $defaults = [
            'start_date' => null,
            'end_date' => null,
            'status' => 'confirmed',
            'user_id' => null,
            'client_id' => null,
            'min_total' => null,
            'max_total' => null,
        ];

        $filters = array_merge($defaults, $filters);

        $filters['start_date'] = $this->parseDate($filters['start_date'], true);
        $filters['end_date'] = $this->parseDate($filters['end_date'], false);
        $filters['status'] = $filters['status'] ?: null;

        return $filters;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['start_date']) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['client_id']) {
            $query->where('client_id', $filters['client_id']);
        }

        if (filled($filters['min_total'])) {
            $query->where('total', '>=', (float) $filters['min_total']);
        }

        if (filled($filters['max_total'])) {
            $query->where('total', '<=', (float) $filters['max_total']);
        }
    }

    private function parseDate(mixed $value, bool $startOfDay): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $startOfDay ? $value->copy()->startOfDay() : $value->copy()->endOfDay();
        }

        $parsed = Carbon::parse($value);
        return $startOfDay ? $parsed->startOfDay() : $parsed->endOfDay();
    }
}
