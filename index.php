<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Menu administrativo demonstrando a estrutura switch case em PHP.">
    <title>Sistema Administrativo | Switch Case</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="pagina">
        <section class="painel" aria-labelledby="titulo-principal">
            <header class="cabecalho">
                <h1 id="titulo-principal">Sistema Administrativo</h1>
                <p class="subtitulo">Clique em qualquer botão para executar uma ação utilizando HTML, CSS e PHP.</p>
            </header>

            <form action="processa.php" method="POST" class="menu" aria-label="Menu administrativo">
                <button type="submit" name="opcao" value="A" class="botao-menu clientes">
                    Cadastro de Clientes
                </button>
                <button type="submit" name="opcao" value="B" class="botao-menu produtos">
                    Cadastro de Produtos
                </button>
                <button type="submit" name="opcao" value="C" class="botao-menu vendas">
                    Relatório de Vendas
                </button>
                <button type="submit" name="opcao" value="D" class="botao-menu estoque">
                    Controle de Estoque
                </button>
                <button type="submit" name="opcao" value="E" class="botao-menu configuracoes">
                    Configurações
                </button>
                <button type="submit" name="opcao" value="F" class="botao-menu sair">
                    Sair do Sistema
                </button>
            </form>

            <div class="mensagem neutra" role="status">
                <p>Escolha uma opção acima</p>
            </div>

            <footer>Exemplo desenvolvido com HTML, CSS e PHP</footer>
        </section>
    </main>
</body>
</html>
