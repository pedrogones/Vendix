# Vendix — direção visual e experiência

Documento de trabalho para organizar a evolução da interface, separado do backlog funcional de `IMPLEMENTACOES.md`.

Data: 21/10/2026. Status: proposta para orientar protótipos e implementação. Nenhuma mudança de interface foi executada nesta etapa.

## 1. Fazer agora ou depois?

Agora: definir organização, padrão visual e fluxos principais. O acabamento das demais telas acompanha a implementação das funcionalidades.

Esperar todas as funcionalidades ficarem prontas tende a espalhar decisões inconsistentes e aumentar o retrabalho. Por outro lado, desenhar todos os módulos futuros em detalhe agora pode gerar telas que serão descartadas quando as regras forem definidas.

O objetivo inicial é ter algumas telas de referência boas o suficiente para orientar o restante do sistema.

## 2. Diagnóstico e limite da análise

A leitura do repositório mostrou diferentes propostas convivendo: apresentação comercial do Vendix, sistema administrativo e catálogo público de uma loja. A primeira correção é deixar claro o papel de cada ambiente.

A aparência precisa ser avaliada com as telas rodando. Não considerar problemas específicos de contraste, responsividade ou espaçamento como confirmados sem inspeção visual.

- [ ] Abrir e registrar o estado atual das telas principais em desktop e celular.
- [ ] Identificar inconsistências de navegação, hierarquia, densidade, formulários e feedback.
- [ ] Registrar as tarefas que exigem passos desnecessários ou deixam o usuário sem saber como continuar.

## 3. Separar as experiências

| Ambiente | Público | Objetivo | Momento |
| --- | --- | --- | --- |
| Sistema de gestão | Comerciante e equipe | Operar vendas, produtos, estoque e resultados | Prioridade inicial |
| Site comercial do Vendix | Potencial cliente do software | Entender o produto, contratar ou acessar | Após definir posicionamento e recursos oferecidos |
| Catálogo público da loja | Consumidor do lojista | Encontrar produtos e iniciar compra/contato | Após decidir seu papel no produto |

Essa separação não exige aplicações diferentes. Exige identidade, linguagem, navegação e destinos coerentes para cada público.

- [ ] Distinguir acesso da equipe, cadastro do comerciante e eventual cadastro do consumidor.
- [ ] Evitar elementos de portal/editorial no fluxo de operação da loja.
- [ ] Se houver catálogo público, destacar a identidade do lojista e sua ação principal.
- [ ] Alinhar a apresentação comercial ao que o produto realmente entrega.

## 4. Direção visual proposta para a gestão

Uma interface clara, prática e consistente, que ajude a trabalhar rápido. A personalidade do Vendix pode aparecer nas cores, na tipografia e nos detalhes sem disputar atenção com produtos, valores e ações.

- Hierarquia evidente: título, contexto, ação principal e conteúdo.
- Poucas cores com função definida; status não depende apenas de cor.
- Formulários com rótulos claros, campos agrupados e erros próximos da origem.
- Tabelas legíveis, com valores monetários alinhados e ações previsíveis.
- Navegação estável, com termos do dia a dia da loja.
- Feedback visível após salvar, vender, remover item ou encontrar erro.
- Densidade adequada à tarefa: o balcão precisa de velocidade; o cadastro pode oferecer mais orientação.
- Uso por teclado no desktop e alvos de toque confortáveis no celular.

Não fechar paleta ou tipografia apenas por preferência abstrata: comparar propostas nas telas reais antes de consolidar.

## 5. Definir o padrão mínimo de interface

- [ ] Paleta: marca, superfícies, textos, bordas, foco e estados de sucesso/alerta/erro.
- [ ] Tipografia: títulos, corpo, rótulos, números e informações auxiliares.
- [ ] Escala de espaçamento, larguras, alinhamentos e comportamento responsivo.
- [ ] Estrutura de navegação, cabeçalho e localização das ações principais.
- [ ] Botões: principal, secundário, destrutivo, carregando e indisponível.
- [ ] Campos: texto, dinheiro, quantidade, busca, seleção, data e imagem.
- [ ] Tabelas: busca, filtros, ordenação, paginação, seleção e ações por registro.
- [ ] Estados: carregamento, vazio inicial, busca sem resultado, erro e sucesso.
- [ ] Modais e painéis: quando usar e como preservar o contexto ao fechar/voltar.
- [ ] Ícones e linguagem: vocabulário consistente, sem depender de ícones sem rótulo para ações importantes.
- [ ] Acessibilidade: contraste, foco visível, rótulos, navegação por teclado e mensagens compreensíveis.

O sistema usa Filament. Aproveitar seus componentes e recursos de tema antes de criar alternativas próprias. Personalizar onde a operação exigir, evitando manter várias versões do mesmo botão, campo ou tabela.

## 6. Três telas de referência

### 6.1. Painel inicial — entender o dia e agir

O painel deve ajudar a decidir o que fazer, com números que tenham significado definido.

- [ ] Destacar venda do dia, quantidade de vendas e ticket médio, com período explícito.
- [ ] Mostrar alertas acionáveis: estoque baixo e situações que precisam de atenção.
- [ ] Oferecer acesso rápido a iniciar venda, cadastrar produto e consultar estoque.
- [ ] Exibir vendas recentes com identificação e situação compreensíveis.
- [ ] Reduzir gráficos e cartões que não ajudam numa decisão concreta.
- [ ] Não apresentar faturamento como recebimento ou lucro.
- [ ] Prever primeiro acesso, ausência de vendas e falha de carregamento.

**Pronto quando:** o comerciante entende como está o dia e encontra a próxima ação sem procurar em vários menus.

### 6.2. Venda de balcão — concluir com rapidez e segurança

- [ ] Dar destaque à busca/leitura do produto e ao carrinho.
- [ ] Mostrar produto, quantidade, preço, desconto e subtotal de maneira fácil de conferir.
- [ ] Manter total e ação principal visíveis conforme o tamanho da tela.
- [ ] Facilitar alteração de quantidade e remoção de item.
- [ ] Deixar a identificação opcional do cliente em posição compatível com sua importância.
- [ ] Mostrar estoque insuficiente e erros sem perder os itens já adicionados.
- [ ] Diferenciar visualmente rascunho, confirmação da venda e recebimento, conforme as regras implementadas.
- [ ] Prever uso por teclado/leitor no desktop e câmera/toque no celular.
- [ ] Definir comportamento após concluir: comprovante, próxima venda e retorno ao painel.

Dependência funcional: recebimento e caixa ainda precisam das implementações do backlog. Protótipos podem representar o fluxo futuro, mas devem indicar o que ainda é proposto.

**Pronto quando:** uma venda comum pode ser montada, conferida e concluída sem dúvidas sobre itens, total e resultado da ação.

### 6.3. Cadastro assistido de produto — ler, conferir e salvar

- [ ] Oferecer leitura de código e cadastro manual como entradas claras.
- [ ] Produto existente: abrir o registro e deixar evidente que é uma edição.
- [ ] Produto novo: preencher sugestões disponíveis e destacar o que falta completar.
- [ ] Produto não encontrado ou consulta indisponível: permitir continuar manualmente com o código preservado.
- [ ] Identificar a origem das sugestões e permitir corrigir dados antes de salvar.
- [ ] Mostrar foto, nome, apresentação/embalagem, código e categoria com boa hierarquia.
- [ ] Separar dados de identificação das decisões comerciais da loja, como preço e, quando implementado, custo.
- [ ] Oferecer “Salvar e ler próximo” para cadastro em sequência.
- [ ] Tratar câmera indisponível, permissão negada e código ilegível com caminho para continuar.
- [ ] Preservar dados preenchidos quando houver erro.

**Pronto quando:** o operador entende o que foi encontrado, o que precisa revisar e como salvar ou continuar o próximo cadastro.

## 7. Como validar os protótipos

Montar as telas com dados representativos, incluindo nomes longos, imagens ausentes, valores variados e listas vazias. Não aprovar apenas uma composição com dados ideais.

- [ ] Comparar as três telas em conjunto para verificar consistência.
- [ ] Percorrer cadastro de produto novo, leitura de produto existente e consulta externa sem resultado.
- [ ] Percorrer venda com vários itens, desconto e tentativa acima do estoque disponível.
- [ ] Conferir navegação, foco e mensagens em desktop e celular.
- [ ] Identificar claramente recursos simulados ou ainda não implementados.
- [ ] Registrar decisões e ajustes necessários antes de expandir o padrão.

O protótipo valida entendimento e usabilidade. A implementação precisa também validar persistência, permissões e regras de negócio.

## 8. Ordem de execução

1. Inspecionar a aplicação atual e registrar os problemas observados.
2. Definir a organização da gestão e separar as jornadas públicas.
3. Comparar uma direção visual nas três telas de referência.
4. Validar os fluxos e consolidar componentes e estilos recorrentes.
5. Implementar as telas respeitando as dependências funcionais.
6. Aplicar o padrão aos demais módulos conforme forem corrigidos ou construídos.
7. Revisar site comercial e eventual catálogo após esclarecer seu papel e suas promessas.

## 9. O que não precisa entrar agora

- Redesenhar em detalhe todos os módulos futuros.
- Criar blog ou portal editorial.
- Transformar a vitrine em comércio eletrônico completo antes de definir sua operação.
- Priorizar animações, efeitos e ilustrações sobre legibilidade e velocidade de uso.
- Trocar a tecnologia da interface sem necessidade demonstrada.

## 10. Entregáveis esperados

- Diagnóstico visual com evidências das telas atuais.
- Mapa simples de navegação e das jornadas principais.
- Referência de cores, tipografia, espaçamento e componentes.
- Protótipos do painel, venda de balcão e cadastro assistido, com estados relevantes em desktop e celular.
- Lista das dependências funcionais e ordem de aplicação do padrão.

**Critério de conclusão desta etapa:** existe uma direção visual consistente e validada nas três tarefas principais, suficiente para orientar as próximas implementações sem redesenhar tudo a cada tela.
