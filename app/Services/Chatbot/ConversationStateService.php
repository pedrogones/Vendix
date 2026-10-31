<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;

class ConversationStateService
{
    private const TTL_MINUTES = 120;

    /**
     * @return array<string,mixed>
     */
    public function get(string $sessionId): array
    {
        $state = Cache::get($this->cacheKey($sessionId));

        if (! is_array($state) || ! isset($state['step'])) {
            return $this->defaultState();
        }

        return array_merge($this->defaultState(), $state);
    }

    /**
     * @param  array<string,mixed>  $changes
     * @return array<string,mixed>
     */
    public function update(string $sessionId, array $changes): array
    {
        $state = array_merge($this->get($sessionId), $changes);

        Cache::put($this->cacheKey($sessionId), $state, now()->addMinutes(self::TTL_MINUTES));

        return $state;
    }

    public function reset(string $sessionId): void
    {
        Cache::forget($this->cacheKey($sessionId));
    }

    /**
     * @return array<string,mixed>
     */
    public function clearPending(string $sessionId): array
    {
        return $this->update($sessionId, [
            'step' => 'idle',
            'pending_product_id' => null,
            'pending_product_name' => null,
            'pending_candidates' => [],
            'pending_quantity' => null,
            'pending_action' => null,
            'status_lookup_order' => null,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function clearCheckoutDraft(string $sessionId): array
    {
        return $this->update($sessionId, [
            'step' => 'idle',
            'pending_product_id' => null,
            'pending_product_name' => null,
            'pending_candidates' => [],
            'pending_quantity' => null,
            'pending_action' => null,
            'buyer_name' => null,
            'buyer_phone' => null,
            'fulfillment_type' => null,
            'delivery_address' => null,
            'status_lookup_order' => null,
        ]);
    }

    private function cacheKey(string $sessionId): string
    {
        return 'chatbot:state:'.$sessionId;
    }

    /**
     * @return array<string,mixed>
     */
    private function defaultState(): array
    {
        return [
            'step' => 'idle',
            'pending_product_id' => null,
            'pending_product_name' => null,
            'pending_candidates' => [],
            'pending_quantity' => null,
            'pending_action' => null,
            'last_intent' => null,
            'buyer_name' => null,
            'buyer_phone' => null,
            'fulfillment_type' => null,
            'delivery_address' => null,
            'needs_human_help' => false,
            'human_help_note' => null,
            'status_lookup_order' => null,
        ];
    }
}
