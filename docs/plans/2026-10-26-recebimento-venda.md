# Vendix — fatia 1: recebimento de venda

Data: 2026-10-26
Solicitado por: usuário do projeto Vendix
Status: concluído — implementação e validação local; atualização da instalação de uso em `docs/VALIDACAO-RECEBIMENTO.md`

## O que foi pedido

Iniciar a Prioridade 1 de `IMPLEMENTACOES.md` §4 com registro de recebimento manual na venda confirmada (dinheiro/Pix/cartão/outro), troco básico e efeito no cancelamento/indicadores — sem gateway, sem caixa diário e sem trocas parciais.

## Entendimento e veredito

Permitida: preservar o ciclo `draft → confirmed → canceled` para estoque; adicionar `payment_status` e `sale_payments`; PDV confirma e recebe na mesma transação; chatbot permanece `unpaid` após confirmação.

## Decisões

1. `payment_status`: `unpaid` | `paid` | `refunded`.
2. Meios: `cash`, `pix`, `card`, `other`.
3. Soma dos pagamentos deve igualar o total; split permitido; parcial fora desta fatia.
4. Troco só em dinheiro (`amount_tendered` − `amount`).
5. Cancelamento de venda paga registra estornos e devolve estoque.
6. Dashboard/relatório de receita filtram `payment_status = paid`.
7. Vendas confirmadas legadas: backfill `paid` + pagamento `other` com nota de legado.

## Arquivos principais

- `database/migrations/2026_10_26_000001_add_sale_payments.php`
- `app/Models/SalePayment.php`, `app/Models/Sale.php`
- `app/Services/Sales/RecordSalePaymentService.php`
- `app/Services/Sales/ConfirmSaleWithPaymentService.php`
- `app/Services/Stocks/CancelSaleService.php`
- `app/Filament/Pages/StartSale.php` e views/CSS do PDV
- `app/Filament/Resources/Sales/SaleResource.php`
- `app/Filament/Pages/SalesDashboard.php`, `SalesReport.php`, `SalesReportService.php`
- `tests/Feature/SalesStockIntegrityTest.php`

## Como validar

- `php artisan test --filter=SalesStockIntegrity`
- Manual: PDV com dinheiro/troco, Pix, dois meios, valor a menos, cancelar venda paga.
- Na instalação de uso: ver `docs/VALIDACAO-RECEBIMENTO.md` (somente `migrate` aditiva).

## O que saiu diferente

Nada material em relação ao plano aprovado. Permissão nova não foi criada; reutilizam-se as de vendas.
