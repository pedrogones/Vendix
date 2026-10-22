# Vendix — revisão da interface do PDV

Data: 21/10/2026. Escopo: venda de balcão em `/admin/start-sale`.

## Organização da operação

1. Buscar um produto por nome ou código.
2. Informar quantidade e eventual desconto no item.
3. Conferir preço, saldo disponível e total do item; adicionar à venda.
4. Identificar o cliente somente se necessário, em área recolhível depois da inclusão.
5. Conferir a lista de itens, subtotal, descontos e total; confirmar a venda.

O botão de adicionar pertence ao formulário do item. A confirmação pertence ao resumo da venda. Nenhum dos dois fica habilitado quando falta seu conteúdo: produto selecionado para adicionar, itens no carrinho para confirmar.

## Identidade visual

- Tipografia do painel, superfícies neutras e destaque em âmbar herdado do Filament.
- Removidos gradientes, título serifado, importação de fontes externas, cartões de indicadores repetidos e CSS global que afetava outras telas.
- Cabeçalho compacto com venda, situação em edição e operador.
- Quantidade e desconto lado a lado, inclusive no celular.
- Tabela no desktop e lista no celular, preservando o nome, código, quantidade, valor e desconto de cada item.
- Resumo lateral em telas largas. No celular, total e confirmação permanecem na barra inferior, com espaço reservado para não cobrir os últimos itens.
- Sem promessas de caixa aberto, recebimento ou troco: o texto descreve apenas confirmação e baixa de estoque implementadas.

## Referências de operação

- Shopify POS, busca e carrinho juntos: https://changelog.shopify.com/posts/shopify-pos-split-screen-home-layout-for-tablet
- Shopify POS 11, leitura de itens/preços e ações por contexto: https://changelog.shopify.com/posts/shopify-pos-version-11-0
- Square, total acessível durante a configuração do item: https://my.squareup.com/help/us/en/article/8633-set-up-and-manage-real-time-check-view-for-restaurants

As referências orientaram hierarquia e operação; não houve cópia de marca, imagens ou inclusão de funcionalidades desses produtos.

## Arquivos

- `app/Filament/Pages/StartSale.php`: ordem, colunas, identificação opcional e atualização da prévia ao selecionar produto.
- `resources/views/filament/pages/components/form-sales.blade.php`: estrutura e apresentação do carrinho/resumo.
- `resources/views/filament/pages/components/pdv-add-item.blade.php`: prévia e ação de inclusão no ponto correto do formulário.
- `public/css/filament/pdv.css`: CSS restrito ao PDV, temas e responsividade.
- `app/Providers/Filament/AdminPanelProvider.php`: versão do URL do CSS baseada na modificação do arquivo, para atualizar o cache do navegador.

## Validação

- 39 testes e 195 asserções da suíte existente passaram, incluindo adicionar/remover/confirmar venda pelo componente Livewire e integridade de estoque.
- CSS analisado pelo PostCSS sem erro de sintaxe.
- PHP formatado com Pint; `git diff --check` aprovado.
- Prévia real no navegador, com usuário/produtos fictícios e SQLite temporário: largura 1440 px no desktop, 390 px no celular e verificação de limites em 320 px.
- Temas claro e escuro inspecionados. Não houve rolagem horizontal nas larguras móveis verificadas.
- Busca com dropdown, atualização da prévia, identificação recolhível, adição de dois produtos, quantidade, desconto e confirmação exercitados no navegador.
- Exemplo validado: duas unidades de café a R$ 18,90 e três unidades de leite a R$ 5,49 com R$ 1,00 de desconto resultaram em R$ 53,27. A confirmação abriu a próxima venda vazia.
- Console do navegador sem erros na verificação após o fluxo.
- Não houve uso de dados da operação ou chamada a Gemini/Cosmos nesta validação.

Não houve teste com teclado virtual de um aparelho físico ou leitor físico; a conferência móvel foi feita por viewport responsivo no navegador.

## Atualização no servidor

```bash
git pull --ff-only origin main
```

O CSS do PDV é servido diretamente de `public/`, com versão no URL. Esta alteração não exige migration nem build Vite. Recarregar a página depois da atualização; se a instalação mantiver views compiladas de versões anteriores, executar `php artisan view:clear`.

## Próximas evoluções

Editar quantidade no item já incluído, leitura por câmera dentro do balcão e pagamento/caixa são evoluções separadas. A revisão não alterou serviços de preço, estoque ou confirmação.
