# Vendix — pedido por conversa (contato, entrega, acompanhamento, handoff)

Data: 2026-10-31. Implementação local; nenhum comando aplicado ao banco da operação nesta sessão.

## Comportamento entregue

- Checkout do chat coleta nome + telefone, depois retirada ou entrega (endereço se entrega).
- Pedido grava `buyer_*`, `fulfillment_*`, `public_token`, `needs_human_help`.
- Loja: página **Pedidos do chat** para avançar status / cancelar atendimento / resolver handoff.
- Cliente: `/pedido/consultar` com número + telefone (throttle).
- Handoff: intent “atendente/humano” marca sessão e, se já houver pedido do telefone, a venda.

## Produção

```bash
php artisan migrate --force
php artisan db:seed --class=ChatOrdersPermissionsSeeder --force
php artisan optimize:clear
```

Não usar `AclSeeder` completo em produção.

## Validação

```bash
php artisan test --filter=ChatOrderFlow
php artisan test --filter=SalesStockIntegrity
php artisan test --filter=ChatbotConversation
```
