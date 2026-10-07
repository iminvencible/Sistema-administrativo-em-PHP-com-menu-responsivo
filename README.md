# Sistema de Manutenção de Motos em PHP

Aplicação didática de uma oficina de motos, feita com PHP nativo, MySQL, HTML5, CSS3 e JavaScript puro. Os seis botões agora abrem formulários e consultas reais. A navegação principal passa pelo único `switch...case` de `index.php`, usando o parâmetro GET `opcao`.

## Executar localmente

Requisitos: PHP 8.2 ou superior, extensões `pdo_mysql` e `mbstring`, MySQL 8.0+ ou MariaDB 10.6+. Não há Composer, npm, CDN nem bibliotecas externas na aplicação.

1. Importe o esquema em um **banco novo** com o MySQL ou pelo phpMyAdmin:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

   O script cria o banco `oficina_motos` e suas tabelas. Se sua conta não puder criar bancos, crie um banco vazio pelo painel, selecione-o, remova as linhas `CREATE DATABASE` e `USE` do script e importe as tabelas.

2. Opcionalmente, importe as três peças de demonstração (pastilha de freio, óleo 20W50 e kit relação). Execute uma única vez:

   ```bash
   mysql -u root -p < database/seed.sql
   ```

   Se usou outro nome de banco, ajuste o `USE` do seed antes da importação. O seed não cria clientes fictícios nem vendas.

3. Copie `config/database.example.php` para `config/database.local.php` e configure host, porta, banco, usuário e senha. O arquivo local é ignorado pelo Git. Também é possível usar as variáveis de ambiente `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD`, que têm prioridade sobre o arquivo.

4. Na raiz do projeto, inicie o servidor de desenvolvimento:

   ```bash
   php -S localhost:8000
   ```

5. Abra [http://localhost:8000](http://localhost:8000). No XAMPP, coloque o projeto em `htdocs`, inicie Apache e MySQL e abra a pasta pelo navegador.

Se a conexão ou as tabelas estiverem indisponíveis, a interface mostra instruções de configuração, sem revelar a senha nem a mensagem interna do banco. PHP e MySQL devem usar uma instalação de 64 bits. As datas de atendimento e filtros usam o fuso de São Paulo (UTC−03:00).

## Os seis módulos

| Botão e tema | Rota | Funcionalidades e tabelas |
| --- | --- | --- |
| Cadastro de Clientes — verde | `index.php?opcao=cliente` | Criar, listar, editar e excluir clientes sem histórico. CPF validado e único, contato, modelo e placa da moto. Tabela `clientes`. |
| Cadastro de Produtos para Motos — azul | `index.php?opcao=produto` | Criar, listar, editar e excluir peças sem histórico. SKU único, categoria, compatibilidade, preço, estoque inicial e mínimo. Tabelas `produtos`, `estoque` e `movimentacoes_estoque`. |
| Relatório de Vendas — laranja | `index.php?opcao=vendas` | Abrir e iniciar OS vinculada ao cliente/CPF/moto, registrar vendas com peças e mão de obra, concluir a OS, filtrar por período e imprimir. Tabelas `ordens_servico`, `vendas` e `venda_itens`, com consulta de `clientes`, `produtos` e baixa de `estoque`. |
| Controle de Estoque — vermelho | `index.php?opcao=estoque` | Entradas e saídas com motivo, alteração do mínimo, saldo, alertas e últimas 50 movimentações. Tabelas `estoque`, `produtos` e `movimentacoes_estoque`. |
| Configurações / Acessibilidade — roxo | `index.php?opcao=configuracoes` | Modo claro/escuro e fonte ampliada, persistidos em `$_SESSION` e na tabela `configuracoes`, por sessão. |
| Sair do Sistema — ciano `#00bcd4` | `index.php?opcao=sair` | Tela de confirmação; o POST registra o encerramento em `sessoes`, apaga o cookie e chama `session_destroy()`. |

A página inicial consulta os totais reais de clientes, peças, alertas e OS abertas. `processa.php` mantém compatibilidade com as opções antigas A–F, apenas redirecionando para o roteador central. Uma opção desconhecida retorna HTTP 404.

A lista de tipos de lojas do enunciado serve como referência de aplicação. Este projeto adota o critério **Oficinas Mecânicas**: peças de reposição e ordens de serviço associadas ao CPF e à moto do cliente.

## Fluxo de atendimento

1. Cadastre o cliente com CPF, modelo e placa da moto.
2. Cadastre as peças e suas quantidades ou registre a reposição no estoque.
3. Em Relatório de Vendas, abra uma OS e, quando começar o trabalho, clique em “Iniciar serviço”.
4. Registre a venda para o mesmo cliente e selecione a OS, se houver. Inclua até 30 linhas de peças e/ou descreva um serviço com valor de mão de obra.
5. O servidor lê os preços do catálogo, soma em centavos, valida o saldo e grava venda, itens, movimentações, baixa de estoque e conclusão da OS em uma transação. Linhas repetidas da mesma peça são somadas. Um erro reverte toda a venda.
6. Filtre as datas e clique em “Imprimir / Salvar PDF”. Escolha “Salvar como PDF” no navegador. O CSS `@media print` remove menu e formulários e mantém cabeçalho, período, itens e total; Ctrl+P também funciona.

O histórico da venda guarda o nome, CPF, moto, placa e preços utilizados no atendimento. Editar cadastros depois não altera relatórios anteriores. Registros com histórico são protegidos por chaves estrangeiras; não existe exclusão ou cancelamento de venda nesta atividade.

Um serviço sem peças é permitido quando tem descrição e mão de obra maior que zero. Para uma venda de peças sem serviço, deixe a mão de obra em zero. Nenhuma saída pode deixar o estoque negativo. Os alertas aparecem quando `quantidade < quantidade_minima`, incluindo indicação textual para não depender só da cor.

## Organização do código

| Arquivo ou pasta | Responsabilidade |
| --- | --- |
| `index.php` | Roteador GET com switch, processamento antes do HTML, mensagens e layout. |
| `app/bootstrap.php` | Sessão, conexão PDO, escape HTML, CSRF e validações. |
| `app/modules.php` | INSERT, SELECT, UPDATE, DELETE e transações de cada módulo. |
| `views/` | Formulários e tabelas de cada opção. |
| `style.css` | Grid responsivo, paletas temáticas, variáveis de claro/escuro e impressão. |
| `app.js` | Impressão, confirmação de exclusão, itens da venda e total estimado. |
| `config/` | Exemplo de configuração e arquivo local ignorado. |
| `database/` | DDL relacional e seed opcional. |
| `tests/integration.py` | Testes HTTP com banco MySQL real e biblioteca padrão Python. |
| `tests/visual.py` | Capturas da interface e impressão real com o Chrome do runner. |
| `.github/workflows/php.yml` | Verificação de sintaxe e testes de integração no GitHub Actions. |

Os formulários continuam funcionando sem JavaScript; nesse caso, a venda usa uma linha de peça e a impressão pode ser feita com Ctrl+P. O tema é aplicado pelo servidor e permanece ao navegar/recarregar. Após sair, a nova sessão começa com as preferências padrão.

## Validação e segurança

- PDO com prepared statements e emulação desativada evita interpolação de entradas no SQL.
- Escape `htmlspecialchars` é aplicado aos dados exibidos.
- Cada alteração exige POST com token CSRF; abrir “Sair” por GET não encerra a sessão.
- Há validações no servidor para CPF, telefone, e-mail, placa, preços, quantidades, datas e vínculo cliente/OS.
- Transações InnoDB e `SELECT ... FOR UPDATE` protegem as baixas de estoque.
- Cookies de sessão usam HttpOnly, SameSite=Lax e Secure quando acessados por HTTPS.
- Os arquivos `.htaccess` limitam o acesso a código interno e SQL no Apache 2.4. Em outro servidor, configure regras equivalentes. O servidor embutido do PHP é para desenvolvimento local.

O projeto demonstra sessões, mas **não possui login de operadores nem controle de permissões**. Os cadastros são compartilhados no banco, e as preferências pertencem à sessão atual. Para disponibilizar dados reais pela internet, o controle de acesso deve ser implementado antes.

## Testes

O GitHub Actions cria um banco descartável com MySQL 8.0 e executa a aplicação PHP. Os testes verificam os seis módulos, a ponte A–F, rotas inválidas, CRUD, validações, CSRF, escape HTML, ordens de serviço, venda com baixa de estoque, rollback por saldo insuficiente, histórico, datas, tema persistente e saída segura.

O workflow também gera capturas em 390px e 1440px e uma impressão PDF no Chrome. Elas ficam no artefato `capturas-oficina` da execução por 14 dias, para revisão visual. Todos os dados das capturas vêm do banco descartável de testes.

Para executar localmente, use somente um banco de teste descartável, importe o esquema e o seed, inicie o PHP na porta 8000 e execute:

```bash
export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=oficina_motos DB_USER=root
export MYSQL_PWD='senha-do-banco-de-teste'
python3 tests/integration.py
```

A variável `MYSQL_PWD` é usada pelo cliente MySQL dos testes; a aplicação usa `DB_PASSWORD` ou o arquivo local. Os testes criam e editam dados. O material PowerPoint original foi preservado como referência da versão inicial do menu.
