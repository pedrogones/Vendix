# Vendix — etapa 1: consistência de vendas e estoque

Data: 2026-10-21
Solicitado por: usuário do projeto Vendix
Status: concluído — implementação e validação local; atualização da instalação de uso documentada separadamente

## O que foi pedido

Iniciar os ajustes de `IMPLEMENTACOES.md`, começando por regras de preço/promoção, movimentação e saldo, confirmação/cancelamento sem duplicação e proteção do histórico.

## Entendimento e veredito

Permitida com condições: preservar os dados existentes, centralizar efeitos transacionais, respeitar permissões e validar em banco de teste isolado. A autorização recebida cobre a investigação e a preparação desta etapa; a skill `plano-de-execucao` exige apresentar o plano escrito antes das alterações de implementação.

Fontes das regras: `IMPLEMENTACOES.md`, seção 3; `app/Services/Stocks/`; `app/Models/Product.php`; recursos de produtos, vendas e movimentações. Não foram encontrados AGENTS.md ou regras `.cursor/rules` no inventário do repositório.

Fora desta etapa: financeiro, recebimentos, custo, novas features de IA, reformulação visual, multiempresa, trocas parciais e saneamento automático de dados históricos.

## Investigação e o que já existe

- `StockService` já registra movimentos e bloqueia o produto ao mudar saldo dentro de transação.
- `ConfirmSaleService` e `CancelSaleService` verificam o status antes da transação, sem bloquear/reler a venda; referências antigas podem passar na verificação.
- `ChatbotCheckoutService` baixa saldo diretamente, sem movimento e sem usar o serviço de confirmação.
- O cancelamento usa motivo `sale_cancel`, ausente no enum da migration de estoque. Usar o motivo já suportado `return`, com referência à venda e descrição de cancelamento, evita alterar o enum existente.
- O CRUD de movimentações cria/edita/apaga histórico sem integrar o saldo.
- `ProductResource` permite escrever saldo diretamente. Há criação normal e edição/cadastro via scanner em `ManageProducts`.
- `Product::final_price` depende da ativação da promoção; o chatbot ignora essa ativação; a vitrine lista promoções apenas por preço preenchido.
- Venda confirmada pode ser excluída pelo recurso de vendas; `StartSale` não revalida o estado antes de alterar itens.
- Testes atuais são exemplos. `phpunit.xml` já configura SQLite em memória, cache e sessão isolados.
- Não existe `vendor/`; PHP e Composer não foram encontrados no PATH desta sessão. Não foi feita conexão ao banco real.
- Há alteração preexistente em `.gitignore` e os documentos de backlog estão não rastreados. Preservar esses arquivos.

## Decisões propostas para esta etapa

1. Promoção válida exige ativação, valor maior que zero e menor que o preço normal. Uma regra central alimenta caixa, chatbot e exibição/listagem de ofertas.
2. Itens já adicionados mantêm seu preço no rascunho do balcão. No chatbot, mudança de preço desde o resumo impede conclusão silenciosa: atualizar o resumo e pedir nova confirmação.
3. Usar a mesma representação de preço, desconto e total nos dois canais: preço unitário efetivo, desconto adicional do item e total calculado, sem contabilizar duas vezes a economia promocional.
4. Operações repetidas sobre a mesma venda não repetem movimentos. O serviço releva o status atual após obter bloqueio; estados incompatíveis recebem erro de domínio.
5. No chatbot, serializar as mutações do carrinho e a conclusão por sessão, com chave estável da tentativa/carrinho e proteção persistente contra criação duplicada. Definir migration aditiva para a chave única e guardar o resultado para repetição da conclusão.
6. Movimentos são imutáveis no fluxo de operação. Corrigir por movimento compensatório com motivo e vínculo ao original. Saldo e histórico mudam na mesma transação.
7. Entrada/saída manuais exigem quantidade inteira positiva e autor real da sessão; ajuste por contagem informa saldo físico e gera apenas a diferença necessária.
8. Cadastro/edição de produto com saldo deve gerar movimento de abertura/ajuste. Bloquear valores negativos e registrar motivo; não reconstituir movimentos antigos artificialmente.
9. Venda concluída ou cancelada mantém histórico; exclusão fica limitada a rascunhos. Cancelamento tem ação explícita no painel, confirmação, motivo e permissão própria, atribuída ao Admin pelo seeder.
10. Desconto adicional não pode exceder o total bruto do item. Uma política de descontos por perfil e limites percentuais fica para etapa própria.

## Passos e arquivos afetados

| Passo | Alterações previstas |
| --- | --- |
| 1. Preparar validação | Localizar runtime PHP/Composer disponível ou preparar dependências após aprovação; inspecionar APIs instaladas do Filament. Não executar seed/migrate no banco real. |
| 2. Preço único | Criar serviço de preço em `app/Services/Products/`; adaptar `app/Models/Product.php`, `app/Services/Chatbot/ProductRuleService.php`, formulário de produtos, `VitrineController` e card da vitrine. |
| 3. Ciclo da venda | Adaptar `ConfirmSaleService`, `CancelSaleService`; centralizar mutações de itens em serviço de vendas; integrar `StartSale` e o gerenciador de itens quando utilizado. Bloquear venda antes de itens e produtos, em ordem determinística. |
| 4. Checkout do chatbot | Adaptar `ChatbotCheckoutService`, `CartService` e `ChatbotService`; adicionar chave de conclusão única na venda via migration aditiva e modelo; usar confirmação compartilhada, recuperação da tentativa e nova confirmação se preço mudar. |
| 5. Estoque manual | Adaptar `StockService`, `StockMovement`, recurso e página de movimentações; criar operação de correção/contagem e migration aditiva para vínculo de compensação quando necessário. Remover edição/exclusão direta e validar no servidor. |
| 6. Saldo no produto | Integrar criação/edição normal e pelo scanner em `ProductResource` e `ManageProducts` ao serviço de abertura/ajuste de saldo. |
| 7. Histórico e acesso | Adaptar `Sale`, `SaleItem`, `SaleResource` e ações em lote para impedir mutação indevida e exclusão de registros concluídos; adicionar ação de cancelamento e permissão no `AclSeeder`. |
| 8. Testar e documentar | Criar testes em `tests/Feature/` e `tests/Unit/`, atualizar apenas itens efetivamente concluídos em `IMPLEMENTACOES.md` e registrar resultados neste plano. |

Mudanças de schema devem ser aditivas, com rollback e sem reescrever migrations aplicadas. Não remover saldos ou movimentos existentes.

## Riscos e pré-condições

- Histórico antigo já pode divergir do saldo. Corrigir os fluxos novos não sana o passado. Levantamento/reconciliação histórica será uma etapa separada, baseada em evidência.
- Banco real precisa ser identificado antes de validar concorrência: SQLite em memória não comprova bloqueio de linha de MySQL/PostgreSQL.
- Cache em produção precisa suportar bloqueio compartilhado entre processos; a configuração será verificada, e a chave única no banco continua necessária para impedir repetição persistente.
- APIs/hook de ações do Filament precisam ser conferidas na versão instalada; validação não pode depender apenas de ocultar botões.
- Novas regras podem rejeitar cadastros/preços antigos inválidos. Mostrar erros claros; não corrigir registros silenciosamente.
- Instalação de runtime/dependências pode exigir acesso à rede/permissões do ambiente. Se indisponível, registrar o bloqueio e não afirmar execução de testes.

## Como validar

- Testes: promoção ativa/inativa/inválida; mesmo preço entre canais; alteração de preço do carrinho exige revisão.
- Vendas: confirmação com múltiplos itens, produto inativo, estoque insuficiente, desconto inválido e rollback integral.
- Repetição: confirmar/cancelar com duas instâncias antigas da mesma venda e repetir conclusão de carrinho sem gerar efeitos extras.
- Estoque: entradas, saídas, contagem, abertura de saldo, edição via scanner e compensação mantêm relação entre variação do saldo e movimentos; histórico não aceita edição/exclusão operacional.
- Acesso: não autorizado não cancela nem ajusta saldo; alteração de itens e exclusão individual/em lote de vendas concluídas são recusadas.
- Executar `php artisan test` em banco isolado e lint dos arquivos alterados com Pint. Verificar migrations apenas no banco de teste.
- Testar concorrência real em conexão isolada com o mesmo motor do ambiente de execução, quando disponível. Explicitar se essa prova não puder ser executada.
- Inspeção manual: venda no balcão e chatbot, movimentos correspondentes, cancelamento e ajuste manual no painel.

## Dúvidas em aberto

As decisões acima são a proposta concreta para aprovação. Não há decisão adicional de produto necessária para iniciar este recorte; runtime e banco disponível para teste são pré-condições técnicas a resolver na execução.

## Resultado

Implementação concluída no checkout após aprovação explícita do usuário (“execute todos”).

- 35 testes da suíte comum (175 asserções) e 6 testes no MySQL descartável (46 asserções) passaram.
- Cobertura de linhas do recorte selecionado de serviços/modelos: 90,23% (314/348); não representa a cobertura global do projeto.
- Pint aprovado nos 30 arquivos PHP alterados e nova conferência dos dois arquivos ajustados por último. `git diff --check` sem erros.
- Dependências instaladas a partir do lock, sem atualização. PHP 8.2 e MySQL 8.4 usados via imagens Docker já disponíveis.
- Criado `SalesStockPermissionsSeeder`, específico para atualização de permissões sem redefinir senha, além de sua chamada pelo seeder original em instalações novas.
- A migration usa `return`, já permitido pelo enum, e inclui chave de checkout, auditoria de cancelamento e referência única de compensação. Rollback/reaplicação testados em SQLite e MySQL.
- Ajuste adicional de compatibilidade: carrinhos anteriores à atualização recebem chave estável sob bloqueio sem perder os itens.
- Teste de rollback revelou necessidade de remover FK e índice único antes da coluna de compensação; corrigido na nova migration.
- Uma migration antiga de estoque tem `down()` vazio. Não foi reescrita; os testes concorrentes recriam apenas o banco MySQL descartável entre os casos.
- Verificação das telas feita por HTTP e ações Livewire; não houve inspeção visual no navegador ou uso de câmera/leitor físico.
- Não houve acesso ou alteração do banco de operação, nem chamadas reais a Gemini/Bluesoft.
- Não foi feita reconciliação histórica. Rascunhos abandonados, política de desconto por perfil, financeiro e reformulação visual continuam nas próximas etapas.

Detalhes e comandos de atualização: `docs/VALIDACAO-VENDAS-ESTOQUE.md`. Itens entregues marcados em `IMPLEMENTACOES.md`.
