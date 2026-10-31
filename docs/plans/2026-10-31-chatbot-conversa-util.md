# Vendix — §5 fatia 1: conversa útil + higiene da IA

Data: 2026-10-31
Solicitado por: usuário do projeto Vendix
Status: implementado (aguardando commit sob pedido)

## Escopo

- Busca com opções quando houver mais de um produto.
- Usar quantidade já presente na mensagem/classificação ao iniciar pedido.
- Resumo do carrinho com todos os itens.
- Sair/voltar nas etapas `awaiting_*` (quantidade, escolha, confirmação).
- Rate limit no `POST /chatbot/message`.
- Logs Gemini: não logar prompt/resposta completos por padrão; retenção documentada.

Fora desta fatia: contato/comprador, retirada/entrega, painel da loja para pedidos, atendimento humano com handoff, FEATURES-IA 1–5/7–10.

## Arquivos

- `ProductRuleService` (candidatos)
- `ChatbotService` + `ConversationStateService`
- `ChatbotController` (throttle)
- `IntentClassifierService` / `config` / `.env.example` (logs)
- testes + `IMPLEMENTACOES` §5 + `FEATURES-IA` #6 parcial + `VALIDACAO-CHATBOT-CONVERSA.md`
