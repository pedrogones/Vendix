<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntentClassifierService
{
    /**
     * @param array<string,mixed> $state
     * @return array{intent:string,entities:array<string,mixed>,confidence:float,source:string}
     */
    public function classify(string $message, array $state = [], ?string $sessionId = null): array
    {
        $fromGemini = $this->classifyWithGemini($message, $state, $sessionId);

        if ($fromGemini !== null) {
            return $fromGemini;
        }

        $fromRules = $this->classifyWithRules($message);

        $this->logInfo('gemini.classification.fallback_rules', [
            'session_id' => $sessionId,
            'message' => $this->truncate($message, 1200),
            'result' => $fromRules,
        ]);

        return $fromRules;
    }

    /**
     * @param array<string,mixed> $state
     * @return array{intent:string,entities:array<string,mixed>,confidence:float,source:string}|null
     */
    private function classifyWithGemini(string $message, array $state, ?string $sessionId): ?array
    {
        $apiKey = (string) config('services.gemini.api_key', '');

        if ($apiKey === '') {
            $this->logWarning('gemini.classification.api_key_missing', [
                'session_id' => $sessionId,
            ]);

            return null;
        }

        $model = (string) config('services.gemini.model', 'gemini-1.5-flash');
        $temperature = (float) config('services.gemini.temperature', 0.1);
        $maxOutputTokens = (int) config('services.gemini.max_output_tokens', 180);
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($model),
        );

        $allowedIntents = [
            'product_lookup',
            'promotions_lookup',
            'order_start',
            'order_remove_item',
            'order_summary',
            'order_confirm',
            'delivery_info',
            'payment_info',
            'fallback',
        ];

        $prompt = sprintf(
            "Classifique a intencao do cliente para atendimento de loja.\n" .
            "Retorne APENAS JSON valido com este formato: {\"intent\":\"...\",\"product_name\":null|string,\"quantity\":null|int,\"confidence\":0-1}.\n" .
            "Intencoes permitidas: %s.\n" .
            "Estado atual da conversa: %s.\n" .
            "Mensagem do cliente: %s",
            implode(', ', $allowedIntents),
            json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $message,
        );

        $this->logInfo('gemini.classification.request', [
            'session_id' => $sessionId,
            'model' => $model,
            'temperature' => $temperature,
            'max_output_tokens' => $maxOutputTokens,
            'message' => $this->truncate($message, 1200),
            'state' => $state,
            'prompt' => $this->shouldLogPrompt() ? $this->truncate($prompt, 12000) : '[disabled]',
            'endpoint' => $endpoint,
        ]);

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($endpoint . '?key=' . $apiKey, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxOutputTokens,
                    ],
                ]);

            $responseBody = $response->json();
            $responseText = data_get($responseBody, 'candidates.0.content.parts.0.text');

            $this->logInfo('gemini.classification.response', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'ok' => $response->ok(),
                'raw_response' => $this->shouldLogResponse() ? $responseBody : '[disabled]',
                'response_text' => is_string($responseText) ? $this->truncate($responseText, 8000) : null,
            ]);

            if (! $response->ok()) {
                return null;
            }

            if (! is_string($responseText) || trim($responseText) === '') {
                $this->logWarning('gemini.classification.empty_response_text', [
                    'session_id' => $sessionId,
                ]);

                return null;
            }

            $parsed = $this->parseGeminiJson($responseText, $sessionId);

            if ($parsed === null) {
                return null;
            }

            if (! in_array($parsed['intent'], $allowedIntents, true)) {
                $this->logWarning('gemini.classification.intent_not_allowed', [
                    'session_id' => $sessionId,
                    'intent' => $parsed['intent'],
                    'allowed_intents' => $allowedIntents,
                ]);

                return null;
            }

            $final = [
                'intent' => $parsed['intent'],
                'entities' => [
                    'product_name' => $parsed['product_name'],
                    'quantity' => $parsed['quantity'],
                ],
                'confidence' => $parsed['confidence'],
                'source' => 'gemini',
            ];

            $this->logInfo('gemini.classification.parsed', [
                'session_id' => $sessionId,
                'result' => $final,
            ]);

            return $final;
        } catch (\Throwable $exception) {
            $this->logError('gemini.classification.exception', [
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
                'trace' => $this->truncate($exception->getTraceAsString(), 5000),
            ]);

            return null;
        }
    }

    /**
     * @return array{intent:string,product_name:string|null,quantity:int|null,confidence:float}|null
     */
    private function parseGeminiJson(string $text, ?string $sessionId = null): ?array
    {
        $candidate = trim($text);

        if (preg_match('/\{.*\}/s', $candidate, $matches) === 1) {
            $candidate = $matches[0];
        }

        $data = json_decode($candidate, true);

        if (! is_array($data)) {
            $this->logWarning('gemini.classification.invalid_json', [
                'session_id' => $sessionId,
                'candidate' => $this->truncate($candidate, 6000),
            ]);

            return null;
        }

        $intent = (string) ($data['intent'] ?? '');
        $productName = isset($data['product_name']) && is_string($data['product_name'])
            ? trim($data['product_name'])
            : null;

        $quantity = isset($data['quantity']) && is_numeric($data['quantity'])
            ? (int) $data['quantity']
            : null;

        $confidence = isset($data['confidence']) && is_numeric($data['confidence'])
            ? (float) $data['confidence']
            : 0.55;

        return [
            'intent' => $intent,
            'product_name' => $productName !== '' ? $productName : null,
            'quantity' => $quantity,
            'confidence' => max(0.0, min(1.0, $confidence)),
        ];
    }

    /**
     * @return array{intent:string,entities:array<string,mixed>,confidence:float,source:string}
     */
    private function classifyWithRules(string $message): array
    {
        $normalized = $this->normalize($message);

        $quantity = $this->extractQuantity($normalized);
        $productName = $this->extractProductName($normalized);

        if (Str::contains($normalized, ['promocao', 'promocoes', 'oferta', 'ofertas', 'desconto'])) {
            return $this->result('promotions_lookup', $productName, $quantity, 0.76, 'rules');
        }

        if (Str::contains($normalized, ['entrega', 'delivery', 'frete'])) {
            return $this->result('delivery_info', $productName, $quantity, 0.82, 'rules');
        }

        if (Str::contains($normalized, ['pagamento', 'pix', 'cartao', 'credito', 'debito', 'parcelamento'])) {
            return $this->result('payment_info', $productName, $quantity, 0.85, 'rules');
        }

        if (Str::contains($normalized, ['resumo', 'carrinho', 'subtotal', 'pedido atual'])) {
            return $this->result('order_summary', $productName, $quantity, 0.79, 'rules');
        }

        if (Str::contains($normalized, ['remover', 'tirar', 'excluir', 'cancelar item'])) {
            return $this->result('order_remove_item', $productName, $quantity, 0.8, 'rules');
        }

        if (Str::contains($normalized, ['confirmar', 'finalizar', 'fechar pedido', 'concluir'])) {
            return $this->result('order_confirm', $productName, $quantity, 0.81, 'rules');
        }

        if (Str::contains($normalized, ['comprar', 'quero', 'adicionar', 'levar', 'montar pedido'])) {
            return $this->result('order_start', $productName, $quantity, 0.72, 'rules');
        }

        if (Str::contains($normalized, ['produto', 'preco', 'valor', 'quanto custa', 'estoque'])) {
            return $this->result('product_lookup', $productName, $quantity, 0.7, 'rules');
        }

        return $this->result('fallback', $productName, $quantity, 0.45, 'rules');
    }

    /**
     * @return array{intent:string,entities:array<string,mixed>,confidence:float,source:string}
     */
    private function result(string $intent, ?string $productName, ?int $quantity, float $confidence, string $source): array
    {
        return [
            'intent' => $intent,
            'entities' => [
                'product_name' => $productName,
                'quantity' => $quantity,
            ],
            'confidence' => $confidence,
            'source' => $source,
        ];
    }

    private function normalize(string $message): string
    {
        return (string) Str::of($message)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim();
    }

    private function extractQuantity(string $normalizedMessage): ?int
    {
        if (preg_match('/\b([1-9][0-9]{0,2})\b/', $normalizedMessage, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractProductName(string $normalizedMessage): ?string
    {
        $patterns = [
            '/(?:produto|item)\s+([a-z0-9\s\-]+)/',
            '/(?:quero|adicionar|comprar|levar|remover|tirar)\s+([a-z0-9\s\-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedMessage, $matches) === 1) {
                $candidate = trim($matches[1]);
                $candidate = preg_replace('/\b(uma|um|duas|dois|tres|quatro|cinco|unidades?)\b/', '', $candidate) ?: $candidate;
                $candidate = trim($candidate);

                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function logInfo(string $event, array $context): void
    {
        Log::channel($this->logChannel())->info($event, $context);
    }

    private function logWarning(string $event, array $context): void
    {
        Log::channel($this->logChannel())->warning($event, $context);
    }

    private function logError(string $event, array $context): void
    {
        Log::channel($this->logChannel())->error($event, $context);
    }

    private function logChannel(): string
    {
        return (string) config('services.gemini.log_channel', 'gemini');
    }

    private function shouldLogPrompt(): bool
    {
        return (bool) config('services.gemini.log_prompt', true);
    }

    private function shouldLogResponse(): bool
    {
        return (bool) config('services.gemini.log_response', true);
    }

    private function truncate(mixed $value, int $max = 4000): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max) . '...[truncated]';
    }
}
