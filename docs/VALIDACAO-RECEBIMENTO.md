# Vendix — entrega da fatia 1 de recebimento

Data: 2026-10-26. Implementação local; não foi aplicado nenhum comando ao banco da operação nesta sessão.

## Comportamento entregue

- Venda mantém `status` de ciclo (rascunho/confirmada/cancelada) para estoque.
- `payment_status` registra `unpaid`, `paid` ou `refunded`.
- No PDV, confirmar exige recebimento(s) cuja soma seja igual ao total. Meios: dinheiro, Pix, cartão, outro.
- Em dinheiro, valor recebido pode ser maior que o valor pago; a diferença é o troco.
- É possível dividir em mais de um meio, desde que a soma feche o total.
- Pedidos do chatbot continuam confirmando estoque sem recebimento (`unpaid`) e não entram na receita até serem pagos.
- Cancelar venda paga gera linhas de estorno e devolve estoque na mesma operação.
- Dashboard e relatório de vendas, no filtro padrão, consideram receita apenas vendas confirmadas e pagas.
- Vendas já confirmadas antes desta migration são marcadas como pagas com um recebimento `other` (“legado pré-recebimento”).

## Atualizar uma instalação existente (produção)

Depois do deploy do código:

```bash
php artisan migrate --force
php artisan db:seed --class=SalesStockPermissionsSeeder --force
# opcional: catálogo demonstrativo com 35 produtos reais de varejo
php artisan db:seed --class=DemoCatalogSeeder --force
php artisan optimize:clear
# se o front do portal/assets mudou e o build não veio no deploy:
npm ci && npm run build
```

- `migrate`: coluna `payment_status` em `sales` + tabela `sale_payments` (aditiva; backfill de vendas confirmadas legadas como pagas).
- `SalesStockPermissionsSeeder`: só se a instalação ainda não rodou o seeder da etapa de vendas/estoque (não altera senhas).
- `DemoCatalogSeeder`: cria categorias e 35 produtos se ainda não existirem (por slug/barcode). Não apaga dados.
- Não usar `AclSeeder` completo em produção (redefine senha da conta padrão).
- Cache compartilhado entre processos (`database` ou Redis); evitar `array` com vários workers.

## Validação automatizada

```bash
php artisan test --filter=SalesStockIntegrity
```
