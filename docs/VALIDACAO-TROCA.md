# Validação — troca de produto

Data: 2026-11-10  
Plano: `docs/plans/2026-11-10-troca-produto.md`

## O que entrou

- Tabelas `sale_exchanges` / `sale_exchange_items`.
- `ExchangeSaleItemsService`: devolve itens + baixa substitutos + acerto financeiro da diferença.
- Reusa `sale_returns` e permissão `return-sales`.
- Ação Filament **Trocar itens**.
- Comprovante lista trocas (crédito/débito/acerto).

## Regras

- Crédito = proporção do total do item devolvido.
- Débito = preço atual do substituto × quantidade.
- Acerto = débito − crédito (pagamento se >, estorno se <, zero se igual).
- Unpaid: só estoque, sem linha financeira; não cancela a venda mesmo se devolver 100%.

## No servidor

```bash
bash scripts/aplicar-entregas.sh
```

## Testes

```bash
php artisan test --filter=SaleExchange
php artisan test --filter=SalePartialReturn
```
