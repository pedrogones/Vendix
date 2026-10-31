# Vendix — entrega do comprovante operacional

Data: 2026-10-31. Implementação local; nenhum comando foi aplicado ao banco da operação nesta sessão.

## Comportamento entregue

- Comprovante HTML da venda (itens, totais, meios, troco, operador, cliente, status).
- Aviso permanente: **não é documento fiscal**.
- Disponível para vendas `confirmed` ou `canceled`; rascunhos não têm comprovante.
- Após confirmar no PDV, a notificação oferece “Ver comprovante”.
- Lista de vendas tem ação “Comprovante”.
- Página com Imprimir, Baixar PDF e Nova venda.
- Sem migration nova (só código/views).

## Atualizar uma instalação existente (produção)

Depois do deploy do código:

```bash
php artisan optimize:clear
```

Não há migration nem seeder nesta fatia.

## Validação automatizada

```bash
php artisan test --filter=SalesStockIntegrity
```

Cobertura: `test_sale_receipt_page_and_pdf_for_confirmed_sale`, `test_sale_receipt_rejects_draft_sale`.
