# Vendix — o que arrumar e o que construir

Documento de trabalho para organizar a evolução do produto. Tom informal, mas com entregas verificáveis.

Base: diagnóstico do repositório em 21/10/2026, atualizado após a implementação da primeira etapa de vendas/estoque. Houve testes automatizados de serviços e ações do painel em ambiente isolado e testes de concorrência no MySQL descartável; não houve acesso ao banco da operação.

Status: primeira etapa de vendas/estoque implementada no checkout. Itens concluídos estão marcados; os demais continuam no backlog. Aplicação da migration e das permissões na instalação de uso está descrita em `docs/VALIDACAO-VENDAS-ESTOQUE.md`.

## 1. Onde estamos hoje

Já existe uma base de gestão: produtos, categorias, clientes, usuários, permissões, venda de balcão, saldo e movimentação de estoque, alertas, dashboards e relatório em PDF.

Também existem cadastro assistido por código de barras/Bluesoft, vitrine pública e chatbot com consulta de catálogo, carrinho e confirmação de pedido. O chatbot usa Gemini para interpretar intenção e regras como alternativa.

A questão principal: as partes existem, mas ainda não formam uma operação consistente de ponta a ponta. A apresentação comercial promete mais do que a implementação encontrada sustenta.

## 2. Portal, site, vitrine… precisamos desembolar isso

Hoje a sensação é de várias ideias de site no mesmo produto. A página inicial apresenta e vende o Vendix; a vitrine mostra produtos de uma loja; o cadastro público cria um cliente, sem uma jornada de criação de empresa. Falta explicar quem está entrando e o que acontece depois.

O incômodo de “portal/blog misturado com vitrine” faz sentido como leitura do produto. No código analisado, porém, não foi identificado um módulo de blog: existe uma landing page comercial e uma vitrine. A aparência de portal não deve virar requisito para construir um blog.

### Direção proposta

- **Site do Vendix:** conversa com o comerciante. Explica o sistema, mostra recursos reais e leva para demonstração, contratação ou acesso.
- **Sistema de gestão:** ambiente da equipe da loja. Produtos, vendas, estoque, caixa e relatórios.
- **Catálogo da loja, se fizer parte da estratégia:** conversa com o consumidor daquela loja. Tem identidade do lojista, produtos, disponibilidade e uma ação de compra ou contato bem definida.

Essa separação é de propósito e navegação. Não exige criar três aplicações ou repositórios agora.

### Implementações

- [ ] Definir o público inicial do Vendix e a principal operação que queremos resolver.
- [ ] Revisar textos e navegação para distinguir “quero usar o Vendix” de “quero comprar desta loja”.
- [ ] Definir o destino de cada cadastro: comerciante/equipe ou consumidor. Criar os fluxos correspondentes.
- [ ] Retirar promessas comerciais de funcionalidades ausentes ou apresentá-las claramente como futuras.
- [ ] Definir o papel de `/vendix-preview`: demonstração com dados próprios ou catálogo real de uma loja. A rota atual não deve ficar ambígua.
- [ ] Evitar exposição de dados reais de operação caso a página seja usada como demonstração.
- [ ] Decidir se catálogo público entra no produto inicial. Até lá, não ampliar a vitrine como se fosse prioridade estabelecida.

**Minha recomendação:** priorizar a gestão. Se o catálogo ficar, começar com uma página simples da loja, busca, categorias, produtos e contato/pedido. Layout de portal e conteúdo editorial não são prioridade. Checkout completo só entra quando houver uma jornada operacional por trás.

**Pronto quando:** qualquer visitante entende se está conhecendo o software, operando a loja ou comprando dela; botões e cadastros levam à jornada correspondente.

## 3. Prioridade 0 — fazer venda e estoque baterem

Entrega e validação desta etapa: `docs/VALIDACAO-VENDAS-ESTOQUE.md`. Saldos históricos não foram reconstruídos; gestão de rascunhos abandonados e política de descontos por perfil continuam pendentes.

### Unificar as regras

- [x] Caixa e chatbot usam preço e confirmação compartilhados, com validação de produto, quantidade e total; cancelamentos passam pelo serviço de devolução.
- [x] Registrar movimentação também nas vendas do chatbot, pelo mesmo serviço usado no balcão.
- [x] Fazer entradas, saídas e contagens manuais alterarem saldo e histórico na mesma transação, com autor real e justificativa.
- [x] Substituir edição/exclusão de movimentos por compensação de lançamentos manuais, vinculada ao original, com motivo e responsável. Movimentos de venda são revertidos pelo cancelamento da venda.
- [x] Controlar alterações diretas do saldo no produto. Cadastro e edição normal/pelo scanner geram abertura ou ajuste com histórico.
- [x] Padronizar promoção: ativada, maior que zero e menor que o preço normal, com comportamento consistente no balcão, chatbot e vitrine.
- [x] Preservar preço capturado no rascunho do balcão; revalidar o preço do carrinho do chatbot e pedir nova confirmação quando mudar.
- [x] Rejeitar desconto negativo ou acima do valor bruto do item.
- [ ] Definir política de concessão de desconto por perfil e limites percentuais; hoje vale a permissão de operar a venda.

### Proteger o ciclo da venda

- [x] Impedir confirmação/cancelamento duplicados com bloqueio e releitura da venda; proteger o checkout por sessão e chave única persistente no banco.
- [x] Restringir criação, edição e remoção de itens a rascunhos, com validação no servidor e recálculo do total.
- [x] Impedir exclusão individual/em lote de vendas confirmadas ou canceladas. A ação explícita de cancelamento devolve estoque; integração financeira permanece na próxima etapa.
- [x] Preservar autor e origem nos movimentos de confirmação/correção e gravar motivo, responsável e data do cancelamento na venda. Operações anônimas do chatbot mantêm origem identificada e autor nulo.
- [ ] Revisar o fluxo de rascunhos: abrir a tela de venda já cria registro; definir retomada e descarte dos abandonados.

**Pronto quando:** caixa e chatbot produzem o mesmo resultado para a mesma operação; toda mudança de saldo tem origem rastreável; repetir uma confirmação/cancelamento não repete seus efeitos.

## 4. Prioridade 1 — completar a venda e o recebimento

- [ ] Separar situação do pedido, situação do pagamento e situação da entrega. Definir o que cada transição faz com o estoque.
- [ ] Implementar registro de recebimentos: dinheiro, Pix, cartão e outros meios definidos para o público inicial.
- [ ] Tratar valor recebido, troco, pagamentos parciais ou divididos conforme o escopo escolhido.
- [ ] Implementar abertura e fechamento de caixa, saldo inicial, entradas/retiradas justificadas e divergências.
- [ ] Disponibilizar comprovante da operação, distinguindo-o de documento fiscal.
- [ ] Integrar cancelamento ao estorno/reembolso quando aplicável.
- [ ] Implementar troca e devolução parcial, com destino do produto devolvido e efeito financeiro explícitos.
- [ ] Unificar os caminhos de criação de venda e as regras de identificação de cliente/vendedor.

Integração automática de pagamento é uma etapa própria. Primeiro, deixar claro se o operador apenas registra um recebimento externo ou se o Vendix efetivamente cobra e confirma a transação.

**Pronto quando:** uma venda pode ser criada, recebida, conferida no caixa e revertida com os efeitos corretos e histórico preservado.

## 5. Prioridade 1 — dar sentido ao pedido por conversa

Dependência: decisão sobre o catálogo público e regras compartilhadas de venda/estoque.

- [ ] Vincular pedido a um comprador/contato com os dados mínimos necessários, sem exigir cadastro completo por padrão.
- [ ] Definir retirada ou entrega e coletar as informações correspondentes.
- [ ] Definir como a loja recebe, aceita e acompanha pedidos e como o cliente consulta o andamento.
- [ ] Impedir que pedido ainda sem pagamento apareça como dinheiro recebido nos indicadores.
- [ ] Mostrar opções quando a pesquisa encontrar produtos parecidos. Hoje a busca retorna o primeiro resultado.
- [ ] Aproveitar a quantidade já informada na mensagem: “quero 2 unidades” não deveria obrigar a repetir a quantidade.
- [ ] Permitir sair da etapa atual, trocar produto, corrigir quantidade e voltar ao resumo.
- [ ] Exibir todos os itens antes da confirmação; hoje o resumo textual limita a lista a cinco produtos, embora o subtotal considere o carrinho inteiro.
- [ ] Oferecer atendimento humano com contexto do pedido.
- [ ] Aplicar limites de requisições e controle de consumo no endpoint público do chatbot.
- [ ] Revisar os dados enviados ao provedor de IA e registrados nos logs, com retenção definida.

**Pronto quando:** o cliente sabe o que pediu, quanto custa e qual é o próximo passo; a loja tem informação suficiente para atender e acompanhar o pedido.

## 6. Prioridade 2 — compras, reposição e resultado de verdade

- [ ] Cadastrar fornecedores e registrar compras/recebimentos vinculados às entradas de estoque.
- [ ] Registrar custo de aquisição e definir o método de custo usado nos relatórios.
- [ ] Preservar o custo relevante da venda para alterações futuras não reescreverem o resultado histórico.
- [ ] Mostrar margem bruta e descontos; distinguir faturamento, recebimento e lucro. Custos/despesas adicionais precisam entrar antes de chamar um indicador de lucro líquido.
- [ ] Implementar contas a pagar/receber e fluxo de caixa, caso continuem no posicionamento do produto.
- [ ] Sugerir reposição por giro, saldo e prazo do fornecedor.
- [ ] Identificar produtos parados e risco de ruptura.
- [ ] Corrigir o indicador “estoque alto”: estar acima do mínimo não significa excesso.
- [ ] Revisar relatórios para não chamar soma de rascunhos ou cancelamentos de receita quando filtros incluírem esses estados.
- [ ] Definir a data de referência dos indicadores. Criar um rascunho hoje e confirmar amanhã não deve atribuir a venda ao dia errado.

**Pronto quando:** o comerciante consegue entender o que vende, quanto recebe, qual a margem e o que precisa repor.

## 7. Prioridade 2 — experiência, catálogo e cadastro

- [ ] Simplificar o fluxo do balcão, com teclado/leitor de código, busca rápida e menos passos para concluir.
- [ ] Revisar validação e normalização de CPF entre cadastro, login e venda. Não criar clientes incompletos a cada alteração do campo.
- [ ] Revisar autenticação pública: limitação de tentativas, renovação da sessão e tratamento de clientes sem usuário associado.
- [x] Corrigir a persistência da descrição em `Product`, validada também na edição pelo scanner.
- [ ] Paginar e filtrar catálogo no servidor; hoje a vitrine carrega todos os produtos ativos.
- [ ] Validar os fluxos principais em celular, incluindo scanner, formulários e confirmação.
- [ ] Implementar variações por cor/tamanho apenas se necessárias para o segmento inicial, com saldo e identificação por variação.
- [ ] Definir necessidade de múltiplas tabelas de preço antes de implementá-las.

**Pronto quando:** os fluxos do segmento escolhido são rápidos, compreensíveis e consistentes entre dispositivos.

### 7.1. Cadastro assistido por leitura — preservar e melhorar

Ler o código para localizar um produto ou preencher seu cadastro faz sentido e deve continuar. A base já existe; a maior parte desta frente é ajuste de experiência e confiabilidade. Usar a leitura em recebimentos e integrar custo são expansões que dependem das entregas de compras/estoque.

**Fluxo atual:** ler código → procurar no cadastro local → abrir edição se existir → tentar dados estruturados do QR ou consulta externa se não existir → abrir formulário para conferir e salvar. Quando a consulta externa não encontra dados, hoje o fluxo avisa, mas não abre automaticamente um cadastro com o código preenchido.

**Proposta de apresentação:** “Cadastro assistido por leitura”. A promessa é apontar a câmera, aproveitar dados encontrados e completar o que falta. Não prometer que qualquer código contém um cadastro completo.

#### Ajustes do cadastro atual

- [ ] Manter a busca local antes da consulta externa, evitando cadastro duplicado e chamadas desnecessárias ao fornecedor.
- [ ] Quando não houver resultado externo, abrir cadastro simplificado com o código já preenchido e permitir concluir manualmente.
- [ ] Permitir cadastro manual também quando a integração estiver indisponível, diferenciando erro de consulta de produto não encontrado.
- [ ] Mostrar a origem dos dados sugeridos, por exemplo: “Dados sugeridos pela Bluesoft — confira embalagem e quantidade”.
- [ ] Tratar nome, marca, descrição, categoria e imagem externos como sugestões revisáveis. Definir como a marca será persistida e exibida; hoje ela vem na consulta, mas não tem campo próprio no modelo.
- [ ] Aproveitar a imagem retornada pela consulta como sugestão de foto, com confirmação e possibilidade de substituição; hoje ela não é aproveitada no formulário desse fluxo.
- [x] Corrigir a persistência da descrição e verificar o resultado depois de salvar e reabrir o produto, incluindo edição pelo scanner.
- [ ] Manter preço de venda, custo e estoque sob decisão da loja. Valor presente em um QR deve aparecer como sugestão identificada, sem ser assumido como preço aprovado.
- [ ] Ao atualizar dados de produto existente, mostrar o que mudaria e permitir escolher os campos, preservando ajustes locais por padrão.
- [ ] Conferir apresentação do produto: unidade, pacote, caixa, volume/peso e variação quando aplicável. Evitar confundir a embalagem consultada com a unidade vendida.
- [ ] Implementar “Salvar e ler próximo”, mantendo o contexto para cadastrar vários produtos em sequência.
- [ ] Garantir operação por câmera, leitor físico e digitação manual. A entrada manual já existe; validar sua integração com leitores que enviam o código e Enter.
- [ ] Validar permissão de câmera, foco, pouca iluminação, código ilegível e recuperação após falha, com instruções curtas para continuar.

#### Uma leitura, uma ação conforme a tela

| Contexto | Comportamento proposto | Dependência |
| --- | --- | --- |
| Cadastro de produtos | Localizar e abrir produto existente ou preparar novo cadastro | Ajustes do fluxo atual |
| Venda de balcão | Localizar produto e adicionar à venda, respeitando quantidade, preço e estoque | Regras compartilhadas de venda |
| Recebimento de mercadoria | Localizar produto e informar quantidade/custo antes de confirmar entrada | Compras, custo e movimentação integrada |

- [ ] Compartilhar a resolução do código entre os contextos, mantendo a ação de cada tela explícita.
- [ ] Evitar que leituras repetidas da câmera cadastrem produtos, adicionem itens ou movimentem estoque sem intenção. No balcão, distinguir repetição acidental de uma nova leitura deliberada.
- [ ] Em recebimentos, gerar movimentação rastreável ao confirmar a entrada; não alterar saldo apenas porque um código foi lido.

#### Código de barras e QR Code com papéis claros

O código de barras normalmente fornece o identificador usado na busca. Um QR pode trazer identificador, link ou dados estruturados; seu conteúdo precisa ser reconhecido antes de virar sugestão de cadastro.

- [ ] Priorizar códigos comerciais de produto e etiquetas internas do Vendix.
- [ ] Definir um formato próprio para etiquetas internas, preservando a distinção entre identificador interno, SKU e código comercial.
- [ ] Explicar quando o QR for um link ou formato não reconhecido, oferecendo leitura de outro código ou entrada manual.
- [ ] Revisar a extração de códigos de URLs: um número qualquer em um link não deve ser tratado automaticamente como identificação confiável de produto.
- [ ] Validar estrutura e limites dos dados recebidos por QR antes de preencher o formulário; manter revisão antes de salvar.

**Ordem desta frente:** corrigir persistência e continuidade quando não há resultado → melhorar revisão de sugestões e embalagem → cadastrar em sequência e validar dispositivos → integrar leitura ao balcão e, depois, aos recebimentos.

**Pronto quando:** produto existente é localizado sem duplicação; produto desconhecido pode ser cadastrado mesmo sem consulta externa; sugestões são revisáveis e persistem corretamente; cada tela executa a ação esperada; leituras acidentais não repetem efeitos.

## 8. Evolução condicionada — várias empresas e planos

Só priorizar esta frente se o modelo escolhido for uma plataforma para várias empresas. Usuários e permissões, sozinhos, não isolam dados de lojas.

- [ ] Definir empresa, loja/filial e vínculo dos usuários.
- [ ] Isolar produtos, clientes, vendas, estoque, arquivos, relatórios, carrinhos e pedidos por empresa.
- [ ] Garantir isolamento nas consultas, ações do painel e endpoints públicos, com testes entre empresas distintas.
- [ ] Configurar identidade, endereço, contatos e catálogo por loja; hoje os dados públicos da empresa vêm de configuração global.
- [ ] Implementar contratação, assinatura e limites de plano se a tabela comercial continuar sendo oferecida.
- [ ] Criar onboarding do comerciante: conta, empresa, equipe, primeiros produtos e primeira venda.

**Pronto quando:** duas empresas operam sem acesso cruzado e cada cliente recebe exatamente os recursos contratados.

## 9. Qualidade e documentação que acompanham as entregas

- [x] Testar venda sem estoque, baixa/devolução, promoção, desconto e rollback em falha.
- [x] Testar concorrência e repetição na confirmação, cancelamento e conclusão do chatbot, incluindo concorrência real no MySQL.
- [x] Testar saldo versus histórico após ajustes e correções.
- [ ] Testar permissões nas ações sensíveis, além da visibilidade dos menus.
- [ ] Validar integrações externas com falha, timeout e resposta inválida.
- [ ] Documentar instalação, configuração, papéis de acesso, integrações e regras de negócio.
- [ ] Definir backup e validar restauração antes de depender do sistema na operação real.

Essas verificações fazem parte das funcionalidades correspondentes; não ficam todas para uma fase final.

## 10. O que vale preservar e desenvolver

- **Cadastro assistido por código de barras:** reduz digitação e tempo de implantação.
- **Conversa ligada ao catálogo real:** pode simplificar pedidos, desde que o fluxo termine numa operação atendível.
- **IA para interpretação, regras para execução:** manter preço, disponibilidade e transições sob controle do sistema.
- **Catálogo compartilhado entre canais:** preservar uma fonte consistente, respeitando o contexto de cada loja.

São diferenciais promissores do projeto. Não há comparação de mercado nesta análise para afirmar que sejam inéditos.

## 11. Ordem sugerida

1. Definir o foco do produto e separar as jornadas de comerciante e consumidor.
2. Corrigir estoque, promoções, exclusões e processamento duplicado.
3. Completar venda, recebimento e caixa.
4. Se o catálogo público permanecer no escopo, completar a operação dos pedidos e do chatbot.
5. Evoluir custo, margem, compras e reposição.
6. Expandir para variações, múltiplas empresas e planos conforme a estratégia validada. Se várias empresas compartilharem a instalação, o isolamento precisa vir antes de colocá-las em operação.

**Critério para a próxima versão:** demonstrar cadastro → venda → recebimento → estoque → cancelamento/devolução → conferência dos números, sem divergências entre os canais.

## Referências no código

- `routes/web.php`: entrada pública, autenticação, preview e chatbot.
- `resources/views/portal/`: apresentação comercial e formulários públicos.
- `resources/views/vitrine/` e `app/Http/Controllers/VitrineController.php`: catálogo público.
- `app/Filament/Pages/StartSale.php`: venda de balcão.
- `app/Services/Stocks/`: confirmação, cancelamento e movimentação.
- `app/Services/Sales/ChatbotCheckoutService.php`: conclusão do pedido por conversa.
- `app/Services/Chatbot/`: interpretação, busca, promoção e condução do pedido.
- `app/Filament/Resources/StockMovements/`: CRUD das movimentações.
- `app/Filament/Resources/Sales/`: gestão e exclusão de vendas.
- `app/Services/Reports/SalesReportService.php`: consolidação do relatório.
- `app/Models/Product.php`: campos e regra de preço do produto.
- `app/Filament/Resources/Products/Pages/ManageProducts.php`: resolução de códigos e abertura de cadastro/edição pelo leitor.
- `app/Services/Products/ProductLookupService.php`: consulta de dados externos na Bluesoft.
- `resources/views/filament/products/scan-product.blade.php` e `public/js/filament/qr-scanner.js`: interface e leitura por câmera, imagem e entrada manual.
- `tests/Feature/SalesStockIntegrityTest.php`: serviços, regras e ações do painel.
- `tests/Concurrency/SalesConcurrencyTest.php`: concorrência real e migration no MySQL descartável, executada separadamente.
