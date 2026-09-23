<?php
// Aceita apenas requisições enviadas pelo formulário do menu.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$opcao = isset($_POST['opcao']) ? strtoupper(trim($_POST['opcao'])) : '';
$titulo = 'Opção inválida';
$mensagem = 'Não foi possível identificar a opção selecionada. Retorne ao menu e tente novamente.';
$icone = '⚠️';
$classe = 'aviso';

switch ($opcao) {
    case 'A':
        $titulo = 'Cadastro de Clientes';
        $mensagem = 'Módulo de Cadastro de Clientes aberto com sucesso!';
        $icone = '👥';
        $classe = 'sucesso';
        break;
    case 'B':
        $titulo = 'Cadastro de Produtos';
        $mensagem = 'Módulo de Cadastro de Produtos aberto com sucesso!';
        $icone = '📦';
        $classe = 'sucesso';
        break;
    case 'C':
        $titulo = 'Relatório de Vendas';
        $mensagem = 'Módulo de Relatório de Vendas aberto com sucesso!';
        $icone = '📈';
        $classe = 'sucesso';
        break;
    case 'D':
        $titulo = 'Controle de Estoque';
        $mensagem = 'Módulo de Controle de Estoque aberto com sucesso!';
        $icone = '🗃️';
        $classe = 'sucesso';
        break;
    case 'E':
        $titulo = 'Configurações';
        $mensagem = 'Módulo de Configurações aberto com sucesso!';
        $icone = '⚙️';
        $classe = 'sucesso';
        break;
    case 'F':
        $titulo = 'Sessão encerrada';
        $mensagem = 'Você saiu do sistema com segurança. Até breve!';
        $icone = '👋';
        $classe = 'saida';
        break;
    default:
        // Mantém a mensagem inicial para valores ausentes ou inesperados.
        break;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Administrativo | Resultado</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="pagina">
        <section class="painel resultado" aria-labelledby="titulo-resultado">
            <h1 id="titulo-resultado">Resultado da seleção</h1>
            <div class="mensagem <?php echo $classe; ?>" role="status">
                <p><?php echo $icone; ?> <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <a class="voltar" href="index.php">← Voltar ao menu</a>
            <footer>Exemplo desenvolvido com HTML, CSS e PHP</footer>
        </section>
    </main>
</body>
</html>
