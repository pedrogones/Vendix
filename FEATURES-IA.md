# Vendix — novas features com IA

Data: 21/10/2026. Status: backlog proposto; nenhuma integração foi implementada nesta etapa.

Este documento complementa `IMPLEMENTACOES.md` e `EVOLUCAO-VISUAL.md`. As features devem seguir as regras de venda, estoque, acesso e interface definidas nesses documentos.

## 1. Objetivo

Usar IA para reduzir o trabalho de alimentar o sistema, encontrar informações e tomar decisões com base nos dados da loja.

Não criar um chat genérico em toda tela. Cada feature deve resolver uma tarefa concreta, com resultado verificável e caminho para continuar quando a IA falhar.

## 2. Ordem sugerida

| Ordem | Feature | Principal benefício | Dependência |
| --- | --- | --- | --- |
| 1 | Importação inteligente de planilhas | Trazer o cadastro da loja com menos trabalho | Importação validada e prévia de alterações |
| 2 | Cadastro assistido por foto e código | Cadastrar produtos sem digitar tudo | Ajustes do leitor e persistência do cadastro |
| 3 | Busca em linguagem natural | Localizar produtos com a descrição usada pelo operador | Busca de catálogo com apresentação de candidatos |
| 4 | Assistente de gestão | Consultar resultados em linguagem simples | Indicadores consistentes e consultas autorizadas |
| 5 | Resumo do dia | Mostrar o que aconteceu e o que merece atenção | Mesmas métricas confiáveis do assistente |
| 6 | Evolução do pedido por conversa | Facilitar pedidos com o catálogo real | Jornada de pedidos, pagamentos e atendimento |
| 7 | Leitura de documentos de compra | Preparar recebimentos com menos digitação | Compras, fornecedores, custo e estoque integrado |
| 8 | Sugestão de reposição | Reduzir ruptura e compras desnecessárias | Histórico confiável, giro e prazo de fornecedor |
| 9 | Alertas de situações fora do padrão | Ajudar a investigar desvios operacionais | Auditoria e histórico consistente |
| 10 | Descrições de produtos | Preparar textos claros para o catálogo | Decisão de manter catálogo público |

As três primeiras formam o pacote inicial proposto. A ordem pode mudar conforme o público e a dificuldade de implantação observada em lojas reais.

## 3. Regras comuns

- IA interpreta, extrai, sugere e explica. O sistema valida e executa as operações.
- Cálculos de preço, margem, saldo e indicadores ficam no código ou em consultas controladas.
- Não permitir SQL arbitrário produzido pelo modelo nem acesso irrestrito ao banco.
- Aplicar permissões e contexto da empresa/loja no servidor, antes de consultar ou alterar dados.
- Mostrar prévia e solicitar confirmação na própria interface antes de importações e alterações sugeridas.
- Não sobrescrever informações locais silenciosamente.
- Não tratar conteúdo de planilhas, imagens, documentos ou produtos como instrução para o modelo executar ações.
- Enviar ao provedor apenas os dados necessários à tarefa; evitar dados pessoais e financeiros identificáveis quando não forem necessários.
- Registrar origem, data, versão da operação e resultado confirmado, sem guardar conteúdo sensível desnecessário nos logs.
- Definir provedor/modelo, limites de consumo, timeout e custo esperado por tarefa antes de disponibilizar cada feature.
- Manter operação manual e mensagens úteis quando houver falha, indisponibilidade ou resultado insuficiente.
- Usar dados de teste representativos para avaliar qualidade antes da liberação.

## 4. Feature 1 — importação inteligente de planilhas

**Problema:** cada loja tem uma planilha diferente, com nomes de colunas, formatos e descrições inconsistentes.

**Exemplo:** reconhecer que “mercadoria”, “vl venda” e “qtd atual” correspondem a nome, preço e saldo informado.

### Escopo inicial

- [ ] Receber planilha e identificar cabeçalhos e possíveis correspondências com campos do Vendix.
- [ ] Usar regras comuns primeiro e IA para sugerir correspondências ambíguas.
- [ ] Permitir ao usuário ajustar o mapeamento antes de processar os registros.
- [ ] Normalizar valores monetários, códigos e textos sem perder zeros iniciais de identificadores.
- [ ] Validar campos obrigatórios e mostrar erros por linha.
- [ ] Apontar possíveis duplicados por código e, como sugestão, por semelhança de nome/apresentação.
- [ ] Mostrar quais linhas serão criadas, atualizadas ou ignoradas, com diferenças nas atualizações.
- [ ] Separar importação de cadastro de eventual carga inicial de estoque, que deve gerar histórico conforme as regras do projeto.
- [ ] Confirmar a importação e apresentar resultado, falhas e possibilidade de corrigir/reprocessar sem duplicar registros concluídos.

**Aceite:** uma planilha com cabeçalhos diferentes pode ser mapeada e revisada; linhas inválidas são identificadas; registros existentes não são sobrescritos sem decisão explícita; repetir a operação não duplica produtos ou efeitos de estoque.

## 5. Feature 2 — cadastro assistido por foto e código

**Problema:** a consulta por código nem sempre encontra dados; cadastrar manualmente todos os atributos demora.

### Escopo inicial

- [ ] Aproveitar o fluxo atual do leitor: procurar localmente e consultar a fonte externa antes de pedir informações adicionais.
- [ ] Permitir foto da embalagem para sugerir nome, marca, apresentação, volume/peso e código legível.
- [ ] Identificar campos ilegíveis ou ambíguos e pedir conferência, sem completar com informações inventadas.
- [ ] Mostrar a origem de cada sugestão: cadastro local, consulta externa ou extração da foto.
- [ ] Separar sugestão de identificação de preço, custo e estoque definidos pela loja.
- [ ] Apresentar formulário revisável antes de salvar e permitir cadastro manual quando a extração falhar.
- [ ] Validar código e possíveis duplicados com regras do sistema.
- [ ] Confirmar embalagem/unidade vendida e persistência dos campos depois de salvar.
- [ ] Integrar a “Salvar e ler próximo” quando o fluxo em sequência estiver disponível.

**Aceite:** a foto reduz digitação sem criar atributos não identificados; o usuário pode corrigir tudo; nenhum cadastro é gravado automaticamente; código já existente leva ao produto correto.

## 6. Feature 3 — busca de produtos em linguagem natural

**Problema:** o operador pode saber a descrição de um produto sem lembrar seu nome exato ou código.

**Exemplo:** “aquele refrigerante de dois litros sem açúcar”.

### Escopo inicial

- [ ] Preservar busca direta por código, SKU e nome para os casos simples.
- [ ] Interpretar termos da consulta como categoria, marca, apresentação e características disponíveis no cadastro.
- [ ] Buscar apenas produtos acessíveis no contexto da loja.
- [ ] Apresentar candidatos com nome, foto, apresentação, preço e disponibilidade reais.
- [ ] Solicitar escolha quando houver ambiguidade; não adicionar automaticamente o primeiro resultado.
- [ ] Informar quando não houver correspondência suficiente e permitir reformular ou usar busca convencional.
- [ ] Compartilhar a busca entre cadastro, balcão e chatbot, respeitando permissões e a ação de cada contexto.

Não escolher antecipadamente uma arquitetura de busca por vetores. Avaliar busca textual, normalização e sinônimos; introduzir mecanismos adicionais se houver ganho demonstrável.

**Aceite:** consultas representativas retornam candidatos relevantes; produtos e atributos não são inventados; consultas por código continuam rápidas; ambiguidades exigem escolha antes de uma operação.

## 7. Feature 4 — assistente de gestão

**Problema:** o comerciante precisa navegar por vários filtros para responder perguntas simples.

**Exemplos:** “Quanto vendi hoje?”, “Quais produtos venderam mais esta semana?”, “O que está abaixo do estoque mínimo?”.

### Escopo inicial

- [ ] Definir um conjunto limitado de perguntas e consultas suportadas.
- [ ] Converter a pergunta em uma consulta permitida, com parâmetros validados no servidor.
- [ ] Explicitar período, filtros, significado da métrica e horário da consulta.
- [ ] Calcular resultados de forma determinística e usar IA para explicar, sem alterar os números.
- [ ] Permitir abrir o relatório ou registros que sustentam a resposta.
- [ ] Distinguir venda, recebimento e margem; responder apenas o que a base suporta.
- [ ] Solicitar esclarecimento quando período ou métrica forem ambíguos.
- [ ] Começar apenas com consultas, sem alteração de preço, estoque ou operações financeiras por conversa.

**Aceite:** respostas batem com os relatórios para o mesmo período/filtro; respeitam permissões; perguntas não suportadas não geram consultas livres nem números inventados.

## 8. Feature 5 — resumo do dia

- [ ] Consolidar métricas reais de vendas, descontos, cancelamentos e alertas de estoque.
- [ ] Gerar um texto curto com período e links para os detalhes.
- [ ] Usar comparações apenas quando houver base equivalente e informar a referência.
- [ ] Não inventar causas para variações; distinguir observação de hipótese.
- [ ] Exibir sob demanda no painel inicialmente. Agendamento e notificações dependem de decisão posterior de produto.

**Aceite:** todos os números podem ser conferidos nos relatórios; falta de dados é explícita; o resumo destaca situações acionáveis sem afirmar causas desconhecidas.

## 9. Feature 6 — evolução do pedido por conversa

O chatbot já existe. Esta feature evolui sua compreensão e experiência sobre a jornada funcional prevista no backlog principal.

- [ ] Interpretar produto e quantidade na mesma mensagem e solicitar apenas o que estiver faltando.
- [ ] Mostrar opções de produto quando a intenção for ambígua.
- [ ] Permitir corrigir quantidade, remover item, trocar seleção e sair da etapa atual.
- [ ] Oferecer alternativas disponíveis quando não houver estoque, sem substituição automática.
- [ ] Mostrar resumo completo e solicitar confirmação antes de concluir.
- [ ] Manter identificação, entrega/retirada, pagamento e acompanhamento conforme a operação implementada.
- [ ] Encaminhar ao atendimento humano com o contexto necessário.
- [ ] Preservar validação de preço, estoque, permissões e proteção contra conclusão duplicada no servidor.

**Aceite:** o cliente consegue revisar o pedido e corrigir erros; a loja consegue atendê-lo; indisponibilidade da IA não produz confirmação falsa nem ignora regras de negócio.

## 10. Feature 7 — leitura de documentos de compra

- [ ] Importar dados estruturados diretamente quando disponíveis, como XML; reservar IA para PDF/foto sem estrutura adequada.
- [ ] Extrair fornecedor, referência do documento, produtos, quantidades, unidades e valores presentes.
- [ ] Sugerir associação dos itens aos produtos locais, com escolha nos casos ambíguos.
- [ ] Conferir totais e sinalizar divergências, campos ilegíveis e possíveis documentos duplicados.
- [ ] Preparar uma prévia de recebimento sem alterar saldo ou custo apenas pela leitura.
- [ ] Confirmar recebimento pelo serviço de compras/estoque e preservar vínculo com o documento.

**Aceite:** itens e valores são revisáveis; divergências bloqueiam conclusão indevida; repetir a leitura não repete entradas; estoque só muda no recebimento confirmado.

## 11. Feature 8 — sugestão de reposição

- [ ] Começar com cálculo explícito de giro, saldo e prazo do fornecedor, usando histórico adequado.
- [ ] Considerar vendas canceladas/devolvidas e reservas quando esses conceitos existirem na operação.
- [ ] Mostrar período, fórmula/parâmetros e dados que sustentam a quantidade sugerida.
- [ ] Usar IA para explicar e organizar prioridades; não delegar o cálculo a uma resposta livre do modelo.
- [ ] Informar quando o histórico for insuficiente e permitir ajustar parâmetros.
- [ ] Preparar sugestão de compra revisável, sem criar pedido ao fornecedor automaticamente.

**Aceite:** a quantidade pode ser reproduzida pelo cálculo e justificada; ausência de histórico não aparece como previsão confiável.

## 12. Feature 9 — situações fora do padrão

- [ ] Iniciar com regras transparentes para descontos incomuns, cancelamentos repetidos e diferenças entre saldo e histórico.
- [ ] Definir comparação, período, limites e volume mínimo para cada alerta.
- [ ] Usar IA para resumir evidências e orientar a investigação, sem atribuir culpa ou afirmar fraude.
- [ ] Permitir abrir registros relacionados, marcar situação como revisada e avaliar falsos positivos.
- [ ] Não bloquear usuários ou operações automaticamente com base apenas na interpretação do modelo.

**Aceite:** cada alerta tem evidência rastreável, critério conhecido e utilidade validada na operação; não é apresentado como prova de irregularidade.

## 13. Feature 10 — descrição de produtos

- [ ] Gerar descrição curta a partir de atributos confirmados do cadastro.
- [ ] Permitir escolher tom e revisar antes de publicar/salvar.
- [ ] Não inventar composição, benefícios, certificações, origem ou características ausentes.
- [ ] Preservar texto existente e oferecer comparação antes de substituir.

**Aceite:** descrição fiel aos dados e editável, sem publicação automática. Priorizar apenas se houver uso real no catálogo ou na operação.

## 14. Validação e entrega

- [ ] Definir exemplos representativos e resultado esperado por feature, incluindo casos ambíguos e falhas.
- [ ] Medir acerto útil, correções exigidas, tempo de resposta e custo por tarefa.
- [ ] Comparar com a operação manual para verificar redução real de trabalho.
- [ ] Testar indisponibilidade, timeout, resposta inválida e limites de consumo.
- [ ] Validar isolamento de loja/empresa e permissões quando aplicáveis.
- [ ] Disponibilizar gradualmente, com configuração para desativar a integração sem impedir a operação básica.
- [ ] Registrar critérios de liberação antes do piloto e revisar com base nos resultados observados.

**Próximo pacote proposto:** importação inteligente + cadastro assistido por foto/código + busca em linguagem natural. Começar pelos ajustes funcionais que sustentam essas experiências e implementar cada feature com prévia, revisão e alternativa manual.
