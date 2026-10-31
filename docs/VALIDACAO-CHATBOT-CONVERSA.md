# Vendix — fatia 1 do pedido por conversa

Data: 2026-10-31. Implementação local; nenhum comando de produção nesta sessão.

## Comportamento entregue

- Busca ambígua lista até 5 opções numeradas; cliente escolhe pelo número.
- Pedido com quantidade na mesma mensagem (`quero 3 agua`) adiciona direto, sem perguntar de novo.
- Resumo do carrinho lista **todos** os itens (não corta em 5).
- Nas etapas de escolha/quantidade/confirmação, `voltar` / `sair` / `cancelar` / `menu` voltam ao idle sem perder o carrinho (exceto se ainda não havia itens).
- `POST /chatbot/message` com throttle `chatbot` (padrão 30/min por sessão; `CHATBOT_RATE_LIMIT_PER_MINUTE`).
- Logs Gemini: `GEMINI_LOG_PROMPT` e `GEMINI_LOG_RESPONSE` padrão `false`; canal daily com `GEMINI_LOG_DAYS` (14).

## Ainda fora desta fatia

- Contato/comprador, retirada/entrega, painel de acompanhamento na loja.
- Handoff humano com contexto.
- Features de IA de cadastro/gestão (`FEATURES-IA` 1–5, 7–10).

## Atualizar instalação existente

```bash
php artisan optimize:clear
# opcional no .env:
# CHATBOT_RATE_LIMIT_PER_MINUTE=30
# GEMINI_LOG_PROMPT=false
# GEMINI_LOG_RESPONSE=false
```

Sem migration.

## Validação

```bash
php artisan test --filter=ChatbotConversation
php artisan test --filter=SalesStockIntegrity
```
