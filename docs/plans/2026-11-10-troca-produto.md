# Troca de produto (fatia 2 do ciclo)

Data: 2026-11-10
Solicitado por: Pedro
Status: concluído

## O que saiu diferente

- Permissão reutilizada (`return-sales`), sem seed novo além do já previsto.
- Script acumulativo `scripts/aplicar-entregas.sh` (legado `aplicar-devolucao-parcial.sh` redireciona).

## Entendimento

Trocar itens de uma venda confirmada: devolver A (restock/loss) e baixar B, com acerto financeiro explícito da diferença. Sem gateway.

## Decisões

1. Reusa `sale_returns` para a parte devolvida; nova entidade `sale_exchanges` + `sale_exchange_items` para os produtos de saída.
2. Não muta `sale_items` originais.
3. Crédito da devolução = proporção do `total_price` do item; débito = preço atual (`final_price`) × qtd.
4. Acerto: se débito > crédito → pagamento; se crédito > débito → estorno; se igual → zero. Operador informa meio; valor deve fechar a diferença.
5. Venda unpaid: só estoque, sem linha financeira.
6. Não cancela a venda automaticamente quando a troca devolve 100% dos itens originais (há substituição).
7. Permissão: reusa `return-sales`.

## Passos

1. Migration + models
2. Flag `cancelIfComplete` em `ReturnSaleItemsService` (uso interno da troca)
3. `ExchangeSaleItemsService`
4. UI Filament + comprovante
5. Testes + docs + script acumulativo
