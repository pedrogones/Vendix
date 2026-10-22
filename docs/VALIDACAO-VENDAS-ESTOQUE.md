# Vendix — entrega da etapa 1 de vendas e estoque

Data: 21/10/2026. Implementação local; não foi aplicado nenhum comando ao banco da operação.

## Comportamento entregue

- Preço promocional tem a mesma regra no balcão, chatbot e vitrine: promoção ativada e preço positivo abaixo do normal.
- Dinheiro é calculado em centavos nos serviços de preço e totalização.
- Confirmação/cancelamento bloqueiam e releem a venda dentro da transação. Produtos são bloqueados em ordem determinística; falhas revertem toda a operação.
- O chatbot usa a confirmação compartilhada e registra saída no histórico. A revisão do carrinho possui chave única persistente na venda; repetir a conclusão recupera a mesma venda sem nova baixa.
- Carrinhos anteriores à atualização recebem a nova chave sob bloqueio, preservando seus itens.
- Mudança de preço no carrinho atualiza o resumo e pede outra confirmação. No rascunho do balcão, o preço capturado é preservado.
- Desconto adicional é separado do preço promocional e não pode exceder o total bruto do item.
- Movimentos manuais, contagens e abertura/alteração de saldo pelo cadastro normal ou scanner geram histórico e saldo na mesma transação.
- Movimento não é editado/apagado pela aplicação. Correção de movimento manual usa compensação vinculada ao original e não pode ser repetida. Para venda, usa-se cancelamento.
- Autor de movimento manual vem da sessão, não de um campo editável.
- Venda confirmada/cancelada não aceita alteração de itens nem exclusão individual/em lote. Cancelamento possui ação no painel, motivo, autor e data; a devolução usa o motivo `return` já aceito pelo banco.
- Cadastro e edição pelo scanner persistem descrição e usam os serviços de produto/estoque. O registro editado pelo scanner é recuperado a cada submissão, sem depender de propriedade protegida entre requisições Livewire.

## Atualizar uma instalação existente

Executar no ambiente da instalação, com sua configuração habitual:

```bash
php artisan migrate
php artisan db:seed --class=SalesStockPermissionsSeeder
```

A migration é aditiva: inclui chave de checkout e dados de cancelamento em vendas e vínculo único de compensação em movimentos. Não reconstrói dados antigos.

O seeder específico cria as permissões de produtos/categorias/vendas/estoque e concede essas permissões, incluindo `cancel-sales`, ao perfil Admin. Não modifica usuários, senhas nem remove concessões existentes. Outros perfis recebem apenas as permissões definidas para sua operação.

Não usar o `AclSeeder` completo para esta atualização: ele mantém o comportamento antigo de redefinir a senha da conta padrão.

O cache precisa ser compartilhado entre processos para serializar conversas/carrinhos. O padrão configurado no projeto é `database`; Redis compartilhado também atende. Não usar cache `array` em operação com múltiplos processos. A restrição única no banco complementa o bloqueio de cache na conclusão.

O rollback desta migration remove seus novos campos e metadados. Não serve para desfazer vendas ou movimentos; essas operações usam cancelamento/compensação.

## Validação automatizada

Suíte comum, com banco isolado conforme `phpunit.xml`:

```bash
APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan test
```

A chave acima é exclusiva de testes e não deve ser usada na instalação de operação.

Os testes cobrem serviços e ações reais do Filament/Livewire: cadastro, edição normal e pelo scanner, baixa/devolução, contagem, compensação, balcão, cancelamento, exclusão em lote e permissões. Também verificam preço alterado no chatbot e rollback/reaplicação da migration no SQLite.

### Concorrência

A suíte `tests/Concurrency` é separada da suíte comum. Exige PHP com `pcntl`, MySQL descartável com banco chamado exatamente `vendix_integrity_test` e autorização explícita pela variável `VENDIX_CONCURRENCY_TESTS=1`.

Ela usa `migrate:fresh` exclusivamente nesse banco de teste. Nunca apontar para uma instalação com dados de uso. As credenciais/conexão devem identificar o MySQL descartável.

```bash
VENDIX_CONCURRENCY_TESTS=1 DB_CONNECTION=mysql DB_DATABASE=vendix_integrity_test php vendor/bin/phpunit tests/Concurrency
```

Cenários: confirmação simultânea, cancelamento simultâneo, checkout simultâneo, duas vendas disputando saldo, compensação simultânea e rollback/reaplicação da migration com dados existentes.

Na execução desta entrega foi usado um contêiner MySQL 8.4 sem portas publicadas, com dados temporários, separado da operação. Uma migration antiga possui `down()` vazio; por isso a suíte recria o banco descartável entre testes, em vez de depender de rollback de todo o histórico de migrations.

## Limites desta entrega

- Não houve inspeção visual no navegador. As ações das telas foram exercitadas por testes Livewire e as páginas por requisições HTTP de teste.
- Nenhuma chamada real ao Gemini ou Bluesoft foi necessária para validar esta etapa. Integração externa e scanner físico/câmera continuam com sua validação operacional pendente.
- A cobertura medida considera os serviços e modelos selecionados de vendas/estoque/preço/carrinho, não todo o projeto.
- Histórico anterior pode já divergir do saldo. Não foram fabricados movimentos retroativos nem corrigidos registros antigos automaticamente.
- Financeiro, estados de pagamento/entrega, rascunhos abandonados e política de desconto por perfil continuam no backlog.
- Composer instalou as versões do lock existente e avisou que o manifesto e o lock já estavam divergentes. Não houve atualização de dependências ou do lock nesta tarefa.

## Resultado final dos checks

- Suíte comum: **35 testes, 175 asserções, todos passando**.
- MySQL descartável: **6 testes, 46 asserções, todos passando** (cinco cenários concorrentes e a reversibilidade da migration).
- Total: **41 testes, 221 asserções**.
- Cobertura de linhas do recorte de serviços/modelos selecionados: **90,23% (314/348)**, medida com PCOV. Este número não é cobertura global do repositório.
- Pint: verificação dos 30 arquivos PHP alterados aprovada; os dois arquivos ajustados por último também foram verificados novamente.
- `git diff --check`: aprovado.
- O MySQL temporário é removido ao encerrar a validação. Nenhum banco da operação foi consultado ou alterado.


## Correção após validação manual — produto criado inativo

O teste manual de balcão revelou um problema do formulário de criação: o `mountUsing` personalizado não inicializava os valores padrão ao abrir um cadastro vazio. Ao preencher dados sugeridos pelo scanner, campos ausentes nas sugestões também podiam perder os padrões. Um teste Livewire reproduziu `is_active = false`, apesar do campo declarar `default(true)`.

Correção: inicializar os padrões do schema e mesclar as sugestões sobre esse estado. O produto inicia ativo tanto no cadastro vazio quanto no cadastro assistido, mantendo a possibilidade de o operador desativá-lo explicitamente. A mensagem de bloqueio da venda agora informa que o produto está inativo e precisa ser ativado no cadastro.

Produtos existentes não são ativados em lote. Para o produto de teste já criado, editar apenas o campo Ativo, salvar e repetir a venda de duas unidades; o saldo anterior de 8 não deve ser alterado durante essa edição.
