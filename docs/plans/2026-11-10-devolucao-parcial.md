# Devolução parcial de venda (fatia 1)

Data: 2026-11-10
Solicitado por: Pedro
Status: concluído

## O que saiu diferente

- `CancelSaleService` passou a repor só a quantidade residual (não devolvida), para não duplicar estoque após devolução parcial.
- Valor padrão do estorno na UI = 0 (efeito financeiro explícito), em vez de sugerir o líquido restante.

## O que foi pedido

Fechar o ciclo operacional pendente: implementar troca/devolução parcial com destino do produto e efeito financeiro explícitos (`IMPLEMENTACOES.md` §4). Decisão de prioridade: fechar o que falta no ciclo, não abrir frentes grandes.

## Entendimento

**Fatia 1 — só devolução parcial.** Sem troca de produto.

Operador devolve uma ou mais quantidades de itens de uma venda **confirmada**, escolhe **destino** do produto e registra o **efeito financeiro** de forma explícita. A venda permanece auditável; cancelamento total (`CancelSaleService`) continua existindo para o caso all-or-nothing.

**Fora do escopo desta fatia:**
- Troca (devolver A e baixar B / diferença de preço)
- Gateway de pagamento / estorno automático no adquirente
- Quarentena / multi-local além de `restock` e `loss`
- Pagamento parcial na venda original
- Refatorar `CancelSaleService` para “ser parcial”

## Investigação

Fonte: código atual + `IMPLEMENTACOES.md` §4 + brief de exploração.

| Peça | Achado |
|---|---|
| Cancel total | `CancelSaleService::cancel` — estoque `in/return` + `refundAll` + `status=canceled` |
| Estorno financeiro | `RecordSalePaymentService::refundAll` — só total; cria linhas `type=refund` |
| Pagamento | `payment_status`: `unpaid \| paid \| refunded` — sem parcial |
| Itens | `SaleItem` imutável após confirm; sem `returned_quantity` |
| Destino | Cancel sempre repõe estoque vendável; `loss` existe só em movimento manual |
| UI | Única ação: Cancelar em `SaleResource` |
| Testes | Cobertura forte de cancel/refund total; zero de devolução parcial |

Lacuna de regra de negócio escrita: o doc pede destino + efeito financeiro, mas não define os valores exatos de destino nem o que acontece quando 100% dos itens voltam. Decisões abaixo fecham essa lacuna para a fatia 1.

## O que já existe

- Lock + transação com retries nos serviços de venda/estoque
- `StockService::in` / `out` com `reason=return|loss`
- `sale_payments` append-only (`payment` / `refund`)
- `Sale::amountPaidCents()` já calcula net (payments − refunds)
- Comprovante já lista estornos
- Caixa já trata `refund` em dinheiro no `expectedCash`
- Permissão `cancel-sales` + padrão de seed Filament

## Decisões e alternativas

1. **Nova operação, não reuso do cancel**  
   Alternativa descartada: tornar `CancelSaleService` parcial — misturaria status `canceled` com venda ainda parcialmente válida.

2. **Persistência em `sale_returns` + `sale_return_items`**  
   Não mutar `sale_items.quantity` (preserva histórico da venda original). Alternativa (`returned_quantity` no item) descartada nesta fatia para não afrouxar a imutabilidade já consolidada.

3. **Destinos:** `restock` (volta ao saldo vendável via `StockService::in` + `reason=return`) ou `loss` (não repõe; registra saída de destino `loss` **somente se** o fluxo exigir rastreio explícito do “não voltou” — ver passo abaixo).  
   Na prática proposta: `restock` → `in/return`; `loss` → **não** chama `in` (produto já saiu na confirmação) e grava o destino na entidade de devolução. Sem inventar local de quarentena.

4. **Financeiro explícito:** operador informa valor + meio do estorno (ou “sem estorno” se unpaid / se escolher não devolver dinheiro nesta operação). Novo `refundAmount` em `RecordSalePaymentService`. Soma dos refunds ≤ soma dos payments.  
   `payment_status`: permanece `paid` enquanto net > 0; vai a `refunded` quando net = 0. **Não** introduzir `partially_refunded` nesta fatia (evita cascata em filtros); dashboards que usam `sales.total` com filtro `paid` precisam passar a considerar net ou excluir o valor estornado — no mínimo ajustar o caminho crítico usado na validação.

5. **Devolução de 100% dos itens:** se qty devolvida acumulada cobre todos os itens **e** (unpaid **ou** net pago = 0), a operação **também** marca a venda como `canceled` com motivo da devolução (reusa transição existente). Se ainda houver net pago > 0, **não** cancela — exige estorno restante ou cancelamento explícito depois.  
   Alternativa (manter `confirmed` mesmo com 100% devolvido) descartada: quebraria o critério “cancelamento/devolução” do ciclo e deixaria vendas “fantasma” confirmadas.

6. **Permissão:** nova `return-sales` (espelha padrão de `cancel-sales`).

## Passos

1. Migration `sale_returns` / `sale_return_items` + models Eloquent — arquivos: `database/migrations/…`, `app/Models/SaleReturn.php`, `app/Models/SaleReturnItem.php`
2. Estender `RecordSalePaymentService` com `refundAmount(Sale, method, amountCents, ?notes)` sem forçar `refunded` se net > 0; ajustar `refundAll` se necessário para coexistir — `app/Services/Sales/RecordSalePaymentService.php`, `app/Models/Sale.php`
3. Criar `ReturnSaleItemsService` (lock sale, validar qty residual, destino, financeiro, estoque, opcionalmente cancelar se 100%) — `app/Services/Sales/ReturnSaleItemsService.php`
4. Seed/Gate da permissão `return-sales` — `database/seeders/SalesStockPermissionsSeeder.php` (+ Policy/AuthServiceProvider se for o padrão do repo)
5. Ação Filament “Devolver itens” em `SaleResource` (itens/qtd, destino, valor/meio, motivo) — `app/Filament/Resources/Sales/SaleResource.php`
6. Comprovante: seção de devoluções (além dos estornos já listados) — `app/Filament/Pages/SaleReceipt.php`, views de receipt
7. Ajuste mínimo de receita/dashboard se um filtro `paid` continuar contando `sales.total` cheio após estorno parcial — localizar uso em dashboard/relatório e alinhar ao net
8. Testes Feature espelhando o padrão de cancel: unpaid, paid parcial, 100%→canceled, anti-replay qty, permissão, painel Livewire — `tests/Feature/…`
9. Atualizar `IMPLEMENTACOES.md` §4 (marcar devolução parcial; deixar troca unchecked) + doc curto `docs/VALIDACAO-DEVOLUCAO.md`

## Arquivos afetados

| Arquivo | Ação |
|---|---|
| `database/migrations/2026_11_10_*_create_sale_returns_tables.php` | criar |
| `app/Models/SaleReturn.php` | criar |
| `app/Models/SaleReturnItem.php` | criar |
| `app/Services/Sales/ReturnSaleItemsService.php` | criar |
| `app/Services/Sales/RecordSalePaymentService.php` | alterar |
| `app/Models/Sale.php` | alterar (relações / regras de payment se preciso) |
| `database/seeders/SalesStockPermissionsSeeder.php` | alterar |
| `app/Filament/Resources/Sales/SaleResource.php` | alterar |
| `app/Filament/Pages/SaleReceipt.php` (+ view parcial) | alterar |
| Dashboard/relatório de receita (a confirmar no código) | alterar se necessário |
| `tests/Feature/SalePartialReturnTest.php` (nome final) | criar |
| `IMPLEMENTACOES.md` | alterar |
| `docs/VALIDACAO-DEVOLUCAO.md` | criar |
| `docs/plans/2026-11-10-devolucao-parcial.md` | este plano |

## Riscos

- Receita continuar usando `sales.total` com `payment_status=paid` após estorno parcial → números inflados.
- Devolver qty acima do residual se concorrência sem lock adequado.
- Confundir devolução parcial com cancelamento no atendimento do chat (`cancelFulfillment` não mexe em estoque/dinheiro).
- `loss` sem movimento de estoque adicional pode parecer “sumiu do rastreio”; a entidade `sale_returns.destination` é a evidência — deixar explícito na UI/comprovante.

## Como vai ser validado

- `php artisan test --filter=SalePartialReturn` (ou nome equivalente) + regressão dos testes de cancel/refund existentes
- Painel: venda paga com 2 itens → devolver 1 unidade `restock` com estorno parcial → estoque +1, refund na sale, venda segue `confirmed`/`paid` com net reduzido
- Mesmo fluxo `loss` → estoque não sobe; return registrado com destino
- Devolver o restante + estornar o net → venda `canceled`/`refunded`
- Usuário sem `return-sales` não executa a ação
- Ambiente: testes isolados do projeto (sem banco da operação)

## Dúvidas em aberto

Nenhuma bloqueante se as decisões acima forem aceitas. Confirmar só:

1. Destinos `restock` | `loss` bastam nesta fatia?
2. Ao devolver 100% + estornar 100%, cancelar a venda automaticamente (proposto) está ok?
