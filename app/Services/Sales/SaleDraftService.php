<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaleDraftService
{
    public function resolveForOperator(int $userId, ?Sale $preferred = null): ?Sale
    {
        Gate::authorize('create-sales');

        return DB::transaction(function () use ($userId, $preferred): ?Sale {
            $this->discardEmptyDrafts($userId);

            if ($preferred && $this->isOwnedDraft($preferred, $userId)) {
                return $preferred->load('items.product', 'client');
            }

            $resumable = Sale::query()
                ->where('user_id', $userId)
                ->where('status', 'draft')
                ->whereHas('items')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            return $resumable?->load('items.product', 'client');
        });
    }

    public function create(int $userId): Sale
    {
        Gate::authorize('create-sales');

        return Sale::create([
            'status' => 'draft',
            'user_id' => $userId,
            'client_id' => null,
            'total' => 0,
        ]);
    }

    public function discard(Sale $sale): void
    {
        Gate::authorize($sale->user_id === auth()->id() ? 'create-sales' : 'edit-sales');

        DB::transaction(function () use ($sale): void {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== 'draft') {
                throw new \DomainException('Só é possível descartar rascunhos.');
            }
            if ($locked->user_id !== auth()->id() && ! auth()->user()?->can('edit-sales')) {
                throw new \DomainException('Você não pode descartar esta venda.');
            }
            $locked->delete();
        });
    }

    public function discardEmptyDrafts(int $userId): int
    {
        $ids = Sale::query()
            ->where('user_id', $userId)
            ->where('status', 'draft')
            ->whereDoesntHave('items')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return Sale::query()->whereIn('id', $ids)->delete();
    }

    private function isOwnedDraft(Sale $sale, int $userId): bool
    {
        return ! $sale->trashed()
            && $sale->status === 'draft'
            && (int) $sale->user_id === $userId;
    }
}
