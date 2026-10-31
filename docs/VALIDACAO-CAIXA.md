# Vendix — entrega de abertura e fechamento de caixa

Data: 2026-10-31. Implementação local; nenhum comando foi aplicado ao banco da operação nesta sessão.

## Comportamento entregue

- Uma sessão de caixa aberta por vez.
- Abertura com saldo inicial (permissão `open-cash`).
- Sangria (`out`) e suprimento (`in`) com motivo obrigatório (`manage-cash-movements`).
- Fechamento com valor contado, esperado calculado e divergência (`close-cash`).
- Esperado = abertura + pagamentos `cash` da sessão − estornos `cash` + suprimentos − sangrias. Pix/cartão/outro não entram no saldo físico.
- Pagamentos do PDV gravam `sale_payments.cash_session_id` na sessão aberta.
- Confirmar venda com recebimento no balcão exige caixa aberto (`ConfirmSaleWithPaymentService`).
- Chatbot continua confirmando estoque sem exigência de caixa (`unpaid`).
- Página Filament `Vendas → Caixa` (`view-cash-register`).

## Atualizar uma instalação existente (produção)

Depois do deploy do código:

```bash
php artisan migrate --force
php artisan db:seed --class=CashRegisterPermissionsSeeder --force
php artisan optimize:clear
```

- `migrate`: tabelas `cash_sessions` e `cash_movements` + coluna `sale_payments.cash_session_id` (aditiva).
- `CashRegisterPermissionsSeeder`: cria permissões e concede ao papel Admin (não altera senhas).
- Não usar `AclSeeder` completo em produção (redefine senha da conta padrão).

## Validação automatizada

```bash
php artisan test --filter=SalesStockIntegrity
```

Cobertura relevante: `test_pdv_confirm_requires_open_cash_session`, `test_cash_session_open_movement_close_and_expected_balance`, `test_chatbot_checkout_does_not_require_cash_session`, além dos testes de recebimento que agora abrem caixa antes de confirmar.
