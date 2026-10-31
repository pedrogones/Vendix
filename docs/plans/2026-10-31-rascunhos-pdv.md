# Vendix — rascunhos abandonados no PDV

Data: 2026-10-31
Solicitado por: usuário do projeto Vendix
Status: concluído

## O que foi pedido

Atacar o residual da P0: fluxo de rascunhos do balcão (retomada e descarte).

## Entendimento

- Abrir o PDV não deve criar `Sale` draft automaticamente.
- Retomar o rascunho mais recente do operador que ainda tenha itens.
- Apagar (soft delete) rascunhos vazios do operador ao entrar no PDV.
- Permitir descartar o rascunho atual na tela.
- Criar draft só no primeiro item (ou se CPF for informado antes).

## Passos

1. Criar `SaleDraftService` — resolver/retomar, limpar vazios, descartar.
2. Adaptar `StartSale` (mount, ensure, confirm, discard).
3. UI: botão Descartar + texto quando ainda não há venda.
4. Testes em `SalesStockIntegrityTest`.
5. Marcar item em `IMPLEMENTACOES.md` §3.

## Fora

Política de desconto por perfil; limpeza global de drafts de outros usuários.
