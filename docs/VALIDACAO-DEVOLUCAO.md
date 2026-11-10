# Validação — devolução parcial

Data: 2026-11-10  
Plano: `docs/plans/2026-11-10-devolucao-parcial.md`

## O que entrou

- Tabelas `sale_returns` / `sale_return_items`.
- Serviço `ReturnSaleItemsService` (itens/qtd, destino, estorno explícito).
- `RecordSalePaymentService::refundAmount` e `refundAll` compatível com estorno parcial prévio.
- Permissão `return-sales`.
- Ação Filament “Devolver itens” em `SaleResource`.
- Comprovante lista devoluções.
- Receita (dashboard, painel, relatório) usa líquido recebimentos − estornos.

## Regras

- Só venda `confirmed`.
- Destinos: `restock` (repor estoque) ou `loss` (não repor).
- Quantidade acumulada ≤ quantidade do item.
- Paga: informar meio + valor do estorno (pode ser 0).
- Não paga: sem linha de estorno.
- 100% dos itens devolvidos e (unpaid ou net=0) → venda `canceled`.
- Cancelamento total continua em `CancelSaleService`.

## Como validar

```bash
php artisan test --filter=SalePartialReturn
php artisan test --filter=SalesStockIntegrity
```

### No servidor (depois do deploy do código)

```bash
bash scripts/aplicar-entregas.sh
# se o mesmo deploy trouxe mudança de portal/assets:
bash scripts/aplicar-entregas.sh --with-build
```

O script acumulativo roda `migrate --force` (devolução + troca), `SalesStockPermissionsSeeder` (`return-sales`) e `optimize:clear`. Migrations aditivas.

Manual: venda paga com 2 unidades → devolver 1 com `restock` e estorno parcial → conferir estoque, comprovante e receita líquida.
