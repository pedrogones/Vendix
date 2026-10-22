# Vendix — ajuste visual do PDV

Data: 2026-10-21
Status: concluído — aprovado pelo usuário e implementado em 21/10/2026

## O que foi pedido

Melhorar rapidamente a tela de venda, tomando PDVs reais como referência e reduzindo a aparência de página promocional/interface decorativa.

## Entendimento

Ajustar apenas apresentação e ordem dos campos da venda de balcão. Preservar serviços, validações, movimentação de estoque e métodos Livewire já testados. Não criar pagamentos, fluxo de caixa ou novos atalhos sem implementação.

## Investigação

- `resources/views/filament/pages/components/form-sales.blade.php`: cabeçalho “Caixa aberto”, três indicadores repetidos, formulário antes do carrinho e resumo chamado de pagamento, com promessa de troco ainda não suportada.
- `public/css/filament/pdv.css`: importa fontes externas, usa título serifado, gradientes, sombras, muitos cartões e regras globais de overflow; também força z-index 9999 nos campos.
- `app/Filament/Pages/StartSale.php`: CPF precede o produto; formulário em uma coluna; métodos de adicionar/remover/confirmar já integrados aos serviços.
- `AdminPanelProvider`: carrega o CSS público diretamente. Deve incluir uma versão no URL do CSS para a alteração aparecer após a atualização, sem depender de cache antigo do navegador.
- Captura enviada pelo usuário confirma excesso de altura, prioridade indevida ao cabeçalho/CPF e afastamento da lista/total.

## Referências reais

- Shopify POS, layout dividido: busca junto ao carrinho e carrinho sempre visível em telas largas. https://changelog.shopify.com/posts/shopify-pos-split-screen-home-layout-for-tablet
- Shopify POS 11: itens, preços e total mais fáceis de ler; ações próximas ao contexto. https://changelog.shopify.com/posts/shopify-pos-version-11-0
- Square: painel do pedido ao lado da configuração de itens, mantendo o total acessível. A referência é de organização da operação, não de copiar recursos de restaurante. https://my.squareup.com/help/us/en/article/8633-set-up-and-manage-real-time-check-view-for-restaurants

## Direção proposta

Desktop: cabeçalho curto com identificação da venda/operador; área principal com formulário compacto e lista de itens; resumo estreito ao lado com subtotal, desconto, total e confirmação.

Celular: produto primeiro; quantidade e desconto na mesma linha quando houver espaço; identificação do cliente recolhida; itens logo abaixo; total e confirmação acessíveis numa barra inferior sem cobrir o conteúdo.

- Usar tipografia do painel, superfícies neutras e bordas discretas.
- Remover gradientes, tipografia serifada, indicadores duplicados e excesso de pills.
- Manter uma hierarquia clara entre adicionar item e confirmar a venda.
- Exibir preço/estoque do produto selecionado em uma linha compacta.
- Estados vazio, adicionando e confirmando objetivos; confirmação desabilitada quando não houver itens.
- Remover textos que sugerem caixa/pagamento/troco implementados.
- Respeitar temas claro/escuro e teclado/foco; evitar CSS global que interfira nas outras telas.

## Passos e arquivos

1. Reorganizar o formulário no `StartSale.php`: produto ocupa largura total, quantidade/desconto dividem colunas e cliente fica em seção recolhível. Não alterar os nomes de campos ou métodos de negócio.
2. Reestruturar `form-sales.blade.php` para busca, itens e resumo, preservando ações, chaves e valores reais. Itens em tabela no desktop e lista compacta no celular.
3. Substituir `pdv.css` por estilos locais ao PDV, responsivos e compatíveis com os componentes do Filament; preservar abertura de dropdowns.
4. Versionar somente o URL do CSS do PDV no `AdminPanelProvider.php` para invalidar cache após atualização.

## Riscos e validação

- Conferir dropdown de busca e interação por teclado após remover regras antigas de empilhamento.
- Conferir barra inferior no celular com área segura, conteúdo longo e teclado aberto.
- Inspecionar layout em desktop e celular com venda vazia e com itens, nos temas claro/escuro, usando dados isolados.
- Rodar os testes existentes de integridade de venda/balcão; validar renderização de Blade e formatação dos PHP alterados.
- CSS público direto não exige reconstruir assets Vite se não houver alteração nesses assets.

## Decisões pendentes

Direção aprovada pelo usuário. Não houve alteração de serviços de negócio ou necessidade de migration.

## Resultado

Implementado e validado: 39 testes/195 asserções passando; CSS válido no PostCSS; PHP formatado com Pint; inspeção real em desktop (1440 px), celular (390 px), limites de largura em 320 px e temas claro/escuro. Busca, prévia, adição com desconto, lista, total e confirmação exercitados com dados fictícios.

O botão de adicionar foi movido para um componente View dentro do schema para aparecer antes da identificação opcional, mantendo os campos e métodos existentes. O novo parcial é `resources/views/filament/pages/components/pdv-add-item.blade.php`.

Relatório e limitações: `docs/PDV-INTERFACE.md`. Nenhum dado de operação foi utilizado. Não houve teste com teclado virtual ou scanner físico.
