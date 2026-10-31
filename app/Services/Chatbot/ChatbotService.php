<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use App\Models\Sale;
use App\Services\Cart\CartService;
use App\Services\Sales\ChatbotCheckoutService;
use App\Services\Sales\ChatOrderLookupService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotService
{
    public function __construct(
        private readonly IntentClassifierService $intentClassifier,
        private readonly ChatbotScopeGuard $scopeGuard,
        private readonly ConversationStateService $stateService,
        private readonly ProductRuleService $productRules,
        private readonly CartService $cartService,
        private readonly ChatbotCheckoutService $checkoutService,
    ) {}

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    public function handle(string $sessionId, string $message): array
    {
        return $this->cartService->synchronized($sessionId, fn (): array => $this->handleUnlocked($sessionId, $message));
    }

    private function handleUnlocked(string $sessionId, string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            return $this->buildResponse(
                'fallback',
                'Digite sua pergunta para eu te ajudar com produtos, promocoes e pedidos.',
                $sessionId,
            );
        }

        $state = $this->stateService->get($sessionId);

        $checkoutSteps = [
            'awaiting_quantity',
            'awaiting_confirmation',
            'awaiting_product_choice',
            'awaiting_contact',
            'awaiting_fulfillment_type',
            'awaiting_delivery_address',
            'awaiting_status_lookup',
        ];

        if (in_array($state['step'], $checkoutSteps, true) && $this->isCancelNavigation($message)) {
            if (in_array($state['step'], ['awaiting_contact', 'awaiting_fulfillment_type', 'awaiting_delivery_address', 'awaiting_confirmation', 'awaiting_status_lookup'], true)) {
                $this->stateService->clearCheckoutDraft($sessionId);
            } else {
                $this->stateService->clearPending($sessionId);
            }

            return $this->buildResponse(
                'step_canceled',
                'Ok, voltei ao menu. Posso ajudar com consulta de produto, promocoes, montar pedido, status, entrega e pagamento.',
                $sessionId
            );
        }

        if ($state['step'] === 'awaiting_product_choice') {
            return $this->handleAwaitingProductChoice($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_quantity') {
            return $this->handleAwaitingQuantity($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_contact') {
            return $this->handleAwaitingContact($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_fulfillment_type') {
            return $this->handleAwaitingFulfillmentType($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_delivery_address') {
            return $this->handleAwaitingDeliveryAddress($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_confirmation') {
            return $this->handleAwaitingConfirmation($sessionId, $message, $state);
        }

        if ($state['step'] === 'awaiting_status_lookup') {
            return $this->handleAwaitingStatusLookup($sessionId, $message, $state);
        }

        $classification = $this->intentClassifier->classify($message, $state, $sessionId);

        $scope = $this->scopeGuard->evaluate(
            $message,
            $classification['intent'] !== 'fallback' || ! empty($classification['entities']['product_name'])
        );

        if (! $scope['allowed']) {
            return $this->buildResponse('out_of_scope', $this->scopeGuard->refusalMessage(), $sessionId, [
                'scope_reason' => $scope['reason'],
            ]);
        }

        $this->stateService->update($sessionId, [
            'last_intent' => $classification['intent'],
        ]);

        return match ($classification['intent']) {
            'product_lookup' => $this->handleProductLookup($sessionId, $message, $classification['entities']),
            'promotions_lookup' => $this->handlePromotionsLookup($sessionId),
            'order_start' => $this->handleOrderStart($sessionId, $message, $classification['entities']),
            'order_remove_item' => $this->handleOrderRemoveItem($sessionId, $message, $classification['entities']),
            'order_summary' => $this->handleOrderSummary($sessionId),
            'order_confirm' => $this->handleOrderConfirm($sessionId),
            'order_status' => $this->handleOrderStatusStart($sessionId),
            'human_help' => $this->handleHumanHelp($sessionId),
            'delivery_info' => $this->handleDeliveryInfo($sessionId),
            'payment_info' => $this->handlePaymentInfo($sessionId),
            default => $this->buildResponse(
                'fallback',
                'Posso te ajudar com: consulta de produto, promocoes, montar pedido, status do pedido, atendimento humano, entrega e pagamento.',
                $sessionId,
                ['classifier_source' => $classification['source']]
            ),
        };
    }

    /**
     * @param  array<string,mixed>  $entities
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleProductLookup(string $sessionId, string $message, array $entities): array
    {
        $query = (string) ($entities['product_name'] ?? $message);
        $candidates = $this->productRules->searchCandidates($query, 5);

        if ($candidates->isEmpty()) {
            return $this->buildResponse(
                'product_lookup',
                'Nao encontrei esse produto. Informe um nome mais especifico para eu consultar.',
                $sessionId
            );
        }

        if ($candidates->count() > 1) {
            return $this->offerProductChoices($sessionId, $candidates, 'lookup', null);
        }

        /** @var Product $product */
        $product = $candidates->first();

        return $this->buildResponse(
            'product_lookup',
            $this->productRules->buildProductSummary($product),
            $sessionId,
            ['product_id' => (int) $product->id]
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handlePromotionsLookup(string $sessionId): array
    {
        $products = $this->productRules->promotionalProducts(4);

        if ($products->isEmpty()) {
            return $this->buildResponse(
                'promotions_lookup',
                'No momento nao ha promocoes ativas. Quer que eu te mostre um produto especifico?',
                $sessionId
            );
        }

        $items = $products->map(function (Product $product): string {
            $pricing = $this->productRules->pricing($product);

            return sprintf(
                '%s (%s, economia de %s)',
                $product->name,
                $this->productRules->formatCurrency($pricing['unit_price']),
                $this->productRules->formatCurrency($pricing['economy'])
            );
        })->implode('; ');

        return $this->buildResponse(
            'promotions_lookup',
            'Promocoes do dia: '.$items.'.',
            $sessionId,
            ['promotions_count' => $products->count()]
        );
    }

    /**
     * @param  array<string,mixed>  $entities
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleOrderStart(string $sessionId, string $message, array $entities): array
    {
        $query = (string) ($entities['product_name'] ?? $message);
        $quantity = $this->normalizeQuantity($entities['quantity'] ?? null)
            ?? $this->extractQuantity($message);
        $candidates = $this->productRules->searchCandidates($query, 5);

        if ($candidates->isEmpty()) {
            return $this->buildResponse(
                'order_start',
                'Para montar o pedido, me diga o nome do produto que voce quer adicionar.',
                $sessionId
            );
        }

        if ($candidates->count() > 1) {
            return $this->offerProductChoices($sessionId, $candidates, 'order', $quantity);
        }

        /** @var Product $product */
        $product = $candidates->first();

        return $this->beginOrderForProduct($sessionId, $product, $quantity);
    }

    /**
     * @param  Collection<int,Product>  $candidates
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function offerProductChoices(string $sessionId, Collection $candidates, string $action, ?int $quantity): array
    {
        $lines = $candidates->values()->map(function (Product $product, int $index): string {
            $pricing = $this->productRules->pricing($product);

            return sprintf(
                '%d) %s — %s (estoque %d)',
                $index + 1,
                $product->name,
                $this->productRules->formatCurrency($pricing['unit_price']),
                (int) $product->stock
            );
        })->implode("\n");

        $this->stateService->update($sessionId, [
            'step' => 'awaiting_product_choice',
            'pending_candidates' => $candidates->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'pending_action' => $action,
            'pending_quantity' => $quantity,
            'pending_product_id' => null,
            'pending_product_name' => null,
        ]);

        return $this->buildResponse(
            'product_choices',
            "Encontrei mais de um produto. Escolha pelo numero:\n{$lines}\n(Digite voltar para cancelar.)",
            $sessionId,
            [
                'candidates' => $candidates->pluck('id')->values()->all(),
                'pending_action' => $action,
            ]
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingProductChoice(string $sessionId, string $message, array $state): array
    {
        $candidates = array_values(array_map('intval', $state['pending_candidates'] ?? []));
        if ($candidates === []) {
            $this->stateService->clearPending($sessionId);

            return $this->buildResponse(
                'product_choices',
                'A lista de opcoes expirou. Informe o produto novamente.',
                $sessionId
            );
        }

        $choice = $this->extractChoiceIndex($message, count($candidates));
        if ($choice === null) {
            return $this->buildResponse(
                'awaiting_product_choice',
                'Responda com o numero da opcao desejada, ou digite voltar para cancelar.',
                $sessionId
            );
        }

        $product = Product::query()->whereKey($candidates[$choice - 1])->first();
        if (! $product instanceof Product) {
            $this->stateService->clearPending($sessionId);

            return $this->buildResponse(
                'product_choices',
                'Essa opcao nao esta mais disponivel. Informe o produto novamente.',
                $sessionId
            );
        }

        $action = (string) ($state['pending_action'] ?? 'lookup');
        $quantity = $this->normalizeQuantity($state['pending_quantity'] ?? null);

        if ($action === 'order') {
            return $this->beginOrderForProduct($sessionId, $product, $quantity);
        }

        $this->stateService->clearPending($sessionId);

        return $this->buildResponse(
            'product_lookup',
            $this->productRules->buildProductSummary($product),
            $sessionId,
            ['product_id' => (int) $product->id]
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function beginOrderForProduct(string $sessionId, Product $product, ?int $quantity): array
    {
        if ($quantity !== null && $quantity > 0) {
            $currentQuantity = $this->cartService->itemQuantity($sessionId, (int) $product->id);
            $validation = $this->productRules->validateForSale($product, $currentQuantity + $quantity);
            if (! $validation['ok']) {
                $this->stateService->clearPending($sessionId);

                return $this->buildResponse('order_start', $validation['message'], $sessionId, [
                    'product_id' => (int) $product->id,
                ]);
            }

            return $this->addProductToCart($sessionId, $product, $quantity);
        }

        $validation = $this->productRules->validateForSale($product, 1);
        if (! $validation['ok']) {
            $this->stateService->clearPending($sessionId);

            return $this->buildResponse('order_start', $validation['message'], $sessionId, [
                'product_id' => (int) $product->id,
            ]);
        }

        $pricing = $this->productRules->pricing($product);
        $priceText = $pricing['is_promotion']
            ? sprintf(
                'Preco promocional: %s (normal: %s).',
                $this->productRules->formatCurrency($pricing['unit_price']),
                $this->productRules->formatCurrency($pricing['normal_price'])
            )
            : sprintf('Preco: %s.', $this->productRules->formatCurrency($pricing['unit_price']));

        $this->stateService->update($sessionId, [
            'step' => 'awaiting_quantity',
            'pending_product_id' => (int) $product->id,
            'pending_product_name' => (string) $product->name,
            'pending_candidates' => [],
            'pending_quantity' => null,
            'pending_action' => null,
        ]);

        return $this->buildResponse(
            'order_start',
            sprintf(
                'Produto selecionado: %s. %s Estoque: %d unidade(s). Quantas unidades voce deseja? (Digite voltar para cancelar.)',
                $product->name,
                $priceText,
                (int) $product->stock
            ),
            $sessionId,
            ['product_id' => (int) $product->id]
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingQuantity(string $sessionId, string $message, array $state): array
    {
        $quantity = $this->extractQuantity($message);

        if ($quantity === null || $quantity <= 0) {
            return $this->buildResponse(
                'awaiting_quantity',
                sprintf(
                    'Informe a quantidade em numero inteiro para "%s", ou digite voltar para cancelar.',
                    $state['pending_product_name'] ?? 'o produto selecionado'
                ),
                $sessionId
            );
        }

        $product = Product::query()->whereKey((int) $state['pending_product_id'])->first();

        if (! $product instanceof Product) {
            $this->stateService->reset($sessionId);

            return $this->buildResponse(
                'awaiting_quantity',
                'O produto selecionado nao esta mais disponivel. Escolha outro item para continuar.',
                $sessionId
            );
        }

        $currentQuantity = $this->cartService->itemQuantity($sessionId, (int) $product->id);
        $requestedTotal = $currentQuantity + $quantity;
        $validation = $this->productRules->validateForSale($product, $requestedTotal);

        if (! $validation['ok']) {
            return $this->buildResponse('awaiting_quantity', $validation['message'], $sessionId);
        }

        return $this->addProductToCart($sessionId, $product, $quantity);
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function addProductToCart(string $sessionId, Product $product, int $quantity): array
    {
        $pricing = $this->productRules->pricing($product);

        $cart = $this->cartService->addItem($sessionId, $product, $quantity, [
            'unit_price' => $pricing['unit_price'],
            'original_price' => $pricing['is_promotion'] ? $pricing['normal_price'] : null,
            'is_promotion' => $pricing['is_promotion'],
        ]);

        $this->stateService->clearPending($sessionId);

        $economyText = $pricing['is_promotion']
            ? ' Economia por unidade: '.$this->productRules->formatCurrency($pricing['economy']).'.'
            : '';

        $messageText = sprintf(
            'Adicionei %dx %s ao carrinho.%s %s',
            $quantity,
            $product->name,
            $economyText,
            $this->buildCartSummaryText($cart)
        );

        $messageText .= ' Voce pode adicionar mais itens, remover item ou digitar "finalizar pedido".';

        return $this->buildResponse('order_item_added', $messageText, $sessionId, [
            'product_id' => (int) $product->id,
            'quantity_added' => $quantity,
        ]);
    }

    /**
     * @param  array<string,mixed>  $entities
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleOrderRemoveItem(string $sessionId, string $message, array $entities): array
    {
        $cart = $this->cartService->get($sessionId);

        if ($cart['total_items'] === 0) {
            return $this->buildResponse('order_remove_item', 'Seu carrinho esta vazio no momento.', $sessionId);
        }

        $query = (string) ($entities['product_name'] ?? $message);
        $product = $this->productRules->findByText($query);

        if (! $product instanceof Product) {
            return $this->buildResponse(
                'order_remove_item',
                'Nao consegui identificar o item para remover. Informe o nome exato do produto.',
                $sessionId
            );
        }

        if ($this->cartService->itemQuantity($sessionId, (int) $product->id) <= 0) {
            return $this->buildResponse(
                'order_remove_item',
                sprintf('O produto "%s" nao esta no carrinho atual.', $product->name),
                $sessionId
            );
        }

        $updatedCart = $this->cartService->removeItem($sessionId, (int) $product->id);

        return $this->buildResponse(
            'order_remove_item',
            sprintf('Removi "%s" do carrinho. %s', $product->name, $this->buildCartSummaryText($updatedCart)),
            $sessionId,
            ['product_id' => (int) $product->id]
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleOrderSummary(string $sessionId): array
    {
        $cart = $this->cartService->get($sessionId);

        return $this->buildResponse('order_summary', $this->buildCartSummaryText($cart), $sessionId);
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleOrderConfirm(string $sessionId): array
    {
        $cart = $this->cartService->get($sessionId);

        if ($cart['total_items'] === 0) {
            return $this->buildResponse(
                'order_confirm',
                'Carrinho vazio. Adicione itens antes de confirmar o pedido.',
                $sessionId
            );
        }

        $state = $this->stateService->get($sessionId);
        if (filled($state['buyer_name']) && filled($state['buyer_phone']) && filled($state['fulfillment_type'])) {
            if ($state['fulfillment_type'] === Sale::FULFILLMENT_DELIVERY && blank($state['delivery_address'])) {
                $this->stateService->update($sessionId, ['step' => 'awaiting_delivery_address']);

                return $this->buildResponse(
                    'awaiting_delivery_address',
                    'Para entrega, informe o endereco completo (rua, numero, bairro). Digite voltar para cancelar.',
                    $sessionId
                );
            }

            return $this->promptFinalConfirmation($sessionId);
        }

        $this->stateService->update($sessionId, ['step' => 'awaiting_contact']);

        return $this->buildResponse(
            'awaiting_contact',
            $this->buildCartSummaryText($cart).' Para concluir, informe seu nome e telefone com DDD (ex.: Maria 11999998888). Digite voltar para cancelar.',
            $sessionId
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingContact(string $sessionId, string $message, array $state): array
    {
        [$name, $phone] = $this->parseContact($message);
        if ($name === null || $phone === null) {
            return $this->buildResponse(
                'awaiting_contact',
                'Nao entendi. Envie no formato: Nome 11999998888 (telefone com DDD).',
                $sessionId
            );
        }

        $this->stateService->update($sessionId, [
            'buyer_name' => $name,
            'buyer_phone' => $phone,
            'step' => 'awaiting_fulfillment_type',
        ]);

        return $this->buildResponse(
            'awaiting_fulfillment_type',
            'Otimo, '.$name.'. Prefere retirada na loja ou entrega? Responda "retirada" ou "entrega".',
            $sessionId
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingFulfillmentType(string $sessionId, string $message, array $state): array
    {
        $normalized = (string) Str::of($message)->lower()->ascii()->trim();
        if (Str::contains($normalized, ['retirada', 'buscar', 'retiro', 'loja'])) {
            $this->stateService->update($sessionId, [
                'fulfillment_type' => Sale::FULFILLMENT_PICKUP,
                'delivery_address' => null,
            ]);

            return $this->promptFinalConfirmation($sessionId);
        }
        if (Str::contains($normalized, ['entrega', 'delivery', 'enviar', 'mandar'])) {
            $this->stateService->update($sessionId, [
                'fulfillment_type' => Sale::FULFILLMENT_DELIVERY,
                'step' => 'awaiting_delivery_address',
            ]);

            return $this->buildResponse(
                'awaiting_delivery_address',
                'Informe o endereco completo para entrega (rua, numero, bairro). Digite voltar para cancelar.',
                $sessionId
            );
        }

        return $this->buildResponse(
            'awaiting_fulfillment_type',
            'Responda apenas "retirada" ou "entrega".',
            $sessionId
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingDeliveryAddress(string $sessionId, string $message, array $state): array
    {
        $address = trim($message);
        if ($address === '' || mb_strlen($address) < 8 || mb_strlen($address) > 255) {
            return $this->buildResponse(
                'awaiting_delivery_address',
                'Endereco invalido. Informe rua, numero e bairro (ate 255 caracteres).',
                $sessionId
            );
        }

        $this->stateService->update($sessionId, [
            'delivery_address' => $address,
        ]);

        return $this->promptFinalConfirmation($sessionId);
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function promptFinalConfirmation(string $sessionId): array
    {
        $state = $this->stateService->get($sessionId);
        $cart = $this->cartService->get($sessionId);
        $typeLabel = Sale::FULFILLMENT_TYPE_LABELS[$state['fulfillment_type']] ?? (string) $state['fulfillment_type'];
        $addressLine = $state['fulfillment_type'] === Sale::FULFILLMENT_DELIVERY
            ? ' Endereco: '.$state['delivery_address'].'.'
            : '';

        $this->stateService->update($sessionId, ['step' => 'awaiting_confirmation']);

        return $this->buildResponse(
            'order_confirm',
            sprintf(
                '%s Contato: %s (%s). Modalidade: %s.%s Digite SIM para confirmar, NAO para cancelar, ou voltar.',
                $this->buildCartSummaryText($cart),
                $state['buyer_name'],
                $state['buyer_phone'],
                $typeLabel,
                $addressLine
            ),
            $sessionId
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingConfirmation(string $sessionId, string $message, array $state): array
    {
        if ($this->isAffirmative($message)) {
            try {
                $order = $this->checkoutService->finalize($sessionId);
                return $this->buildResponse(
                    'order_confirmed',
                    sprintf(
                        'Pedido confirmado pelo chat! Numero: %s. Total: %s. Pagamento sera confirmado na loja. Acompanhe em /pedido/consultar com o numero do pedido e o telefone %s.',
                        $order['order_number'],
                        $this->productRules->formatCurrency((float) $order['total']),
                        $order['buyer_phone']
                    ),
                    $sessionId,
                    [
                        'sale_id' => $order['sale_id'],
                        'order_number' => $order['order_number'],
                        'public_token' => $order['public_token'],
                    ]
                );
            } catch (\App\Exceptions\CartPriceChangedException $exception) {
                $this->stateService->update($sessionId, ['step' => 'awaiting_confirmation']);

                return $this->buildResponse('order_price_changed', $exception->getMessage().' '.$this->buildCartSummaryText($this->cartService->get($sessionId)).' Digite SIM para confirmar ou NAO para cancelar.', $sessionId);
            } catch (\DomainException $exception) {
                $this->stateService->update($sessionId, ['step' => 'idle']);

                return $this->buildResponse('order_confirm_error', $exception->getMessage(), $sessionId);
            }
        }

        if ($this->isNegative($message)) {
            $this->stateService->update($sessionId, ['step' => 'idle']);

            return $this->buildResponse(
                'order_confirmation_canceled',
                'Confirmacao cancelada. Seu carrinho foi mantido para voce continuar depois.',
                $sessionId
            );
        }

        return $this->buildResponse(
            'awaiting_confirmation',
            'Responda com SIM para confirmar o pedido, NAO para cancelar a confirmacao, ou voltar.',
            $sessionId,
            ['current_step' => $state['step'] ?? 'awaiting_confirmation']
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleOrderStatusStart(string $sessionId): array
    {
        $this->stateService->update($sessionId, [
            'step' => 'awaiting_status_lookup',
            'status_lookup_order' => null,
        ]);

        return $this->buildResponse(
            'order_status',
            'Para consultar o andamento, informe o numero do pedido e o telefone usado na compra (ex.: 000123 11999998888).',
            $sessionId
        );
    }

    /**
     * @param  array<string,mixed>  $state
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleAwaitingStatusLookup(string $sessionId, string $message, array $state): array
    {
        $digitsOnly = preg_replace('/\D+/', ' ', $message) ?? '';
        $parts = array_values(array_filter(explode(' ', trim($digitsOnly)), fn (string $part): bool => $part !== ''));
        if (count($parts) < 2) {
            return $this->buildResponse(
                'awaiting_status_lookup',
                'Nao entendi. Envie no formato: 000123 11999998888.',
                $sessionId
            );
        }

        $orderNumber = $parts[0];
        $phone = implode('', array_slice($parts, 1));
        $sale = app(ChatOrderLookupService::class)->findByOrderAndPhone($orderNumber, $phone);
        $this->stateService->clearPending($sessionId);

        if (! $sale) {
            return $this->buildResponse(
                'order_status_not_found',
                'Nao encontrei esse pedido com o telefone informado. Confira o numero e tente de novo.',
                $sessionId
            );
        }

        return $this->buildResponse(
            'order_status',
            $this->formatSaleStatusMessage($sale),
            $sessionId,
            ['sale_id' => $sale->id, 'fulfillment_status' => $sale->fulfillment_status]
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleHumanHelp(string $sessionId): array
    {
        $state = $this->stateService->get($sessionId);
        $cart = $this->cartService->get($sessionId);
        $note = sprintf(
            'Handoff %s | step=%s | itens=%d | subtotal=%s | contato=%s %s',
            now()->format('d/m H:i'),
            $state['step'] ?? 'idle',
            $cart['total_items'],
            $this->productRules->formatCurrency((float) $cart['subtotal']),
            $state['buyer_name'] ?? '-',
            $state['buyer_phone'] ?? '-'
        );
        $note = mb_substr($note, 0, 255);

        $this->stateService->update($sessionId, [
            'needs_human_help' => true,
            'human_help_note' => $note,
        ]);

        if (filled($state['buyer_phone'])) {
            $existing = Sale::query()
                ->where('origin', 'chatbot')
                ->where('status', 'confirmed')
                ->where('buyer_phone', Sale::normalizePhone((string) $state['buyer_phone']))
                ->orderByDesc('id')
                ->first();
            if ($existing) {
                $existing->updateFulfillment([
                    'needs_human_help' => true,
                    'human_help_note' => $note,
                ]);
            }
        }

        $phone = env('COMPANY_PHONE', 'telefone nao informado');

        return $this->buildResponse(
            'human_help',
            sprintf(
                'Combinei: a loja foi avisada para te atender com o contexto do seu pedido. Se preferir, ligue para %s. Voce pode continuar o pedido aqui quando quiser.',
                $phone
            ),
            $sessionId,
            ['needs_human_help' => true]
        );
    }

    private function formatSaleStatusMessage(Sale $sale): string
    {
        $fulfillment = Sale::FULFILLMENT_STATUS_LABELS[$sale->fulfillment_status] ?? (string) $sale->fulfillment_status;
        $type = Sale::FULFILLMENT_TYPE_LABELS[$sale->fulfillment_type] ?? (string) $sale->fulfillment_type;
        $payment = Sale::PAYMENT_STATUS_LABELS[$sale->payment_status] ?? (string) $sale->payment_status;
        $help = $sale->needs_human_help ? ' Atendimento humano solicitado.' : '';

        return sprintf(
            'Pedido %s: atendimento "%s" (%s). Pagamento: %s. Total %s.%s',
            $sale->orderNumber(),
            $fulfillment,
            $type,
            $payment,
            $this->productRules->formatCurrency((float) $sale->total),
            $help
        );
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function parseContact(string $message): array
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');
        if (preg_match('/^(.*?)(\+?\d[\d\s().-]{8,}\d)\s*$/u', $message, $matches) !== 1) {
            return [null, null];
        }
        $name = trim($matches[1], " \t\n\r\0\x0B,.-");
        $phone = Sale::normalizePhone($matches[2]);
        if ($name === '' || mb_strlen($name) > 120 || strlen($phone) < 10 || strlen($phone) > 13) {
            return [null, null];
        }

        return [$name, $phone];
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handleDeliveryInfo(string $sessionId): array
    {
        $phone = env('COMPANY_PHONE', 'telefone nao informado');
        $address = env('COMPANY_ADDRESS', 'endereco nao informado');

        return $this->buildResponse(
            'delivery_info',
            sprintf(
                'Para entrega e prazo, confirme seu endereco no atendimento oficial (%s). Retirada: %s.',
                $phone,
                $address
            ),
            $sessionId
        );
    }

    /**
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function handlePaymentInfo(string $sessionId): array
    {
        $phone = env('COMPANY_PHONE', 'telefone nao informado');

        return $this->buildResponse(
            'payment_info',
            sprintf(
                'As formas de pagamento (pix, cartao e parcelamento) sao confirmadas no atendimento da loja: %s.',
                $phone
            ),
            $sessionId
        );
    }

    /**
     * @param  array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}  $cart
     */
    private function buildCartSummaryText(array $cart): string
    {
        if ($cart['total_items'] === 0) {
            return 'Seu carrinho esta vazio.';
        }

        $lines = [];
        foreach ($cart['items'] as $item) {
            $lines[] = sprintf(
                '%dx %s (%s)',
                (int) $item['quantity'],
                (string) $item['name'],
                $this->productRules->formatCurrency((float) $item['line_total'])
            );
        }

        return sprintf(
            'Resumo: %s. Subtotal: %s (%d item(ns)).',
            implode('; ', $lines),
            $this->productRules->formatCurrency((float) $cart['subtotal']),
            (int) $cart['total_items']
        );
    }

    private function extractQuantity(string $message): ?int
    {
        if (preg_match('/\b([1-9][0-9]{0,2})\b/', Str::lower($message), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalizeQuantity(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $quantity = (int) $value;

        return $quantity > 0 ? $quantity : null;
    }

    private function extractChoiceIndex(string $message, int $max): ?int
    {
        if (preg_match('/\b([1-9][0-9]?)\b/', trim($message), $matches) !== 1) {
            return null;
        }
        $index = (int) $matches[1];
        if ($index < 1 || $index > $max) {
            return null;
        }

        return $index;
    }

    private function isCancelNavigation(string $message): bool
    {
        $normalized = (string) Str::of($message)->lower()->ascii()->trim();

        return in_array($normalized, ['voltar', 'sair', 'cancelar', 'cancela', 'menu', 'parar'], true);
    }

    private function isAffirmative(string $message): bool
    {
        $normalized = (string) Str::of($message)->lower()->ascii()->trim();

        return in_array($normalized, ['sim', 's', 'confirmar', 'confirmo', 'ok', 'pode confirmar'], true);
    }

    private function isNegative(string $message): bool
    {
        $normalized = (string) Str::of($message)->lower()->ascii()->trim();

        return in_array($normalized, ['nao', 'n', 'cancelar', 'cancela', 'parar'], true);
    }

    /**
     * @param  array<string,mixed>  $extraMeta
     * @return array{intent:string,message:string,meta:array<string,mixed>}
     */
    private function buildResponse(string $intent, string $message, string $sessionId, array $extraMeta = []): array
    {
        $cart = $this->cartService->get($sessionId);
        $state = $this->stateService->get($sessionId);

        return [
            'intent' => $intent,
            'message' => $message,
            'meta' => array_merge([
                'step' => $state['step'],
                'cart_total_items' => $cart['total_items'],
                'cart_subtotal' => $cart['subtotal'],
            ], $extraMeta),
        ];
    }
}
