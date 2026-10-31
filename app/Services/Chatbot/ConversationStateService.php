<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;

class ConversationStateService
{
    private const TTL_MINUTES = 120;

    /**
     * @return array{
     *     step:string,
     *     pending_product_id:int|null,
     *     pending_product_name:string|null,
     *     pending_candidates:list<int>,
     *     pending_quantity:int|null,
     *     pending_action:string|null,
     *     last_intent:string|null
     * }
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
     * @return array{
     *     step:string,
     *     pending_product_id:int|null,
     *     pending_product_name:string|null,
     *     pending_candidates:list<int>,
     *     pending_quantity:int|null,
     *     pending_action:string|null,
     *     last_intent:string|null
     * }
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
     * @return array{
     *     step:string,
     *     pending_product_id:int|null,
     *     pending_product_name:string|null,
     *     pending_candidates:list<int>,
     *     pending_quantity:int|null,
     *     pending_action:string|null,
     *     last_intent:string|null
     * }
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
        ]);
    }

    private function cacheKey(string $sessionId): string
    {
        return 'chatbot:state:'.$sessionId;
    }

    /**
     * @return array{
     *     step:string,
     *     pending_product_id:int|null,
     *     pending_product_name:string|null,
     *     pending_candidates:list<int>,
     *     pending_quantity:int|null,
     *     pending_action:string|null,
     *     last_intent:string|null
     * }
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
        ];
    }
}
