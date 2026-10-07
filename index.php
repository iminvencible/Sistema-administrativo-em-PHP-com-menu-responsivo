<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/modules.php';

$requested = raw($_GET, 'opcao') ?: 'inicio';
$route = $requested;
// Único roteador principal: o GET seleciona apenas arquivos conhecidos.
switch ($requested) {
    case 'inicio': $title = 'Sua oficina, organizada.'; $handler = 'home_module'; $view = 'inicio'; break;
    case 'cliente': $title = 'Cadastro de Clientes'; $handler = 'clients_module'; $view = 'clientes'; break;
    case 'produto': $title = 'Cadastro de Produtos para Motos'; $handler = 'products_module'; $view = 'produtos'; break;
    case 'vendas': $title = 'Relatório de Vendas'; $handler = 'sales_module'; $view = 'vendas'; break;
    case 'estoque': $title = 'Controle de Estoque'; $handler = 'stock_module'; $view = 'estoque'; break;
    case 'configuracoes': $title = 'Configurações / Acessibilidade'; $handler = 'settings_module'; $view = 'configuracoes'; break;
    case 'sair': $title = 'Sair do Sistema'; $handler = 'logout_module'; $view = 'sair'; break;
    case 'encerrado': $title = 'Sessão encerrada'; $handler = null; $view = 'encerrado'; $route = 'sair'; break;
    default: $title = 'Página não encontrada'; $handler = null; $view = '404'; $route = 'inicio'; http_response_code(404); break;
}

$error = '';
$dbError = false;
$data = [];
$connection = null;
$post = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), raw($_POST, 'csrf'))) {
        $error = 'A sessão do formulário expirou. Recarregue a página e tente novamente.';
        http_response_code(403);
        $post = [];
    } elseif ($route === 'inicio' || $handler === null) {
        $error = 'Esta página não aceita alterações.';
        http_response_code(405);
        $post = [];
    }
}
if ($handler) {
    try {
        $connection = db();
        track_session($connection);
        $data = $handler($connection, $post);
    } catch (DomainException $e) {
        if ($connection && $connection->inTransaction()) { $connection->rollBack(); }
        $error = $e->getMessage();
        http_response_code(422);
        $data = $handler($connection, []);
    } catch (PDOException $e) {
        if ($connection && $connection->inTransaction()) { $connection->rollBack(); }
        if ($requested === 'sair' && raw($post, 'acao') === 'sair') { end_session($connection); }
        if ($connection && $e->getCode() === '23000') {
            $error = 'CPF ou SKU já cadastrado, ou registro vinculado a um histórico. Registros com histórico não podem ser excluídos.';
            http_response_code(422);
            $data = $handler($connection, []);
        } else {
            error_log('Falha de banco de dados na oficina: ' . $e->getCode());
            $dbError = true;
            http_response_code(503);
        }
    }
}
$theme = ($_SESSION['tema'] ?? 'claro') === 'escuro' ? 'escuro' : 'claro';
$large = !empty($_SESSION['fonte_ampliada']);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$menus = [
    'cliente' => ['01', 'Cadastro de Clientes', 'Pessoas e suas motos'],
    'produto' => ['02', 'Cadastro de Produtos para Motos', 'Catálogo de peças'],
    'vendas' => ['03', 'Relatório de Vendas', 'Serviços e faturamento'],
    'estoque' => ['04', 'Controle de Estoque', 'Entradas, saídas e alertas'],
    'configuracoes' => ['05', 'Configurações / Acessibilidade', 'Tema e acessibilidade'],
    'sair' => ['06', 'Sair do Sistema', 'Encerrar esta sessão'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="<?= h($theme) ?>" class="<?= $large ? 'fonte-ampliada' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de manutenção de motos: clientes, peças, vendas e estoque.">
    <title><?= h($title) ?> | Moto Oficina</title>
    <link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
</head>
<body class="modulo-<?= h($route) ?>">
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<div class="pagina">
    <header class="topbar no-print">
        <a href="index.php" class="marca" aria-label="Moto Oficina, início"><span class="marca-simbolo" aria-hidden="true">M<span>O</span></span><span>Moto Oficina<small>GESTÃO DE MANUTENÇÃO</small></span></a>
        <span class="session-label"><span class="status-dot" aria-hidden="true"></span><?= $requested === 'encerrado' ? 'Sessão encerrada' : 'Ambiente da oficina' ?></span>
    </header>
    <main id="conteudo" tabindex="-1">
        <header class="cabecalho no-print">
            <p class="eyebrow">PAINEL ADMINISTRATIVO <span>/ <?= h($route === 'inicio' ? 'VISÃO GERAL' : $menus[$route][1]) ?></span></p>
            <h1><?= h($title) ?></h1>
            <p class="subtitulo">Do primeiro atendimento à última peça. Acompanhe a rotina da sua oficina.</p>
        </header>
        <nav class="menu no-print" aria-label="Menu principal">
            <?php foreach ($menus as $key => [$number, $label, $description]): ?>
                <a class="botao-menu <?= h($key) ?>" href="index.php?opcao=<?= h($key) ?>" <?= $route === $key ? 'aria-current="page"' : '' ?>>
                    <span class="menu-number" aria-hidden="true"><?= h($number) ?></span><span class="menu-title"><?= h($label) ?><small><?= h($description) ?></small></span><span class="menu-arrow" aria-hidden="true">↗</span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if ($flash): ?><div class="mensagem sucesso no-print" role="status"><?= h($flash) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mensagem erro no-print" role="alert"><?= h($error) ?></div><?php endif; ?>
        <section class="painel" aria-label="<?= h($title) ?>">
            <?php if ($dbError): ?>
                <div class="empty-state"><p class="eyebrow">CONEXÃO INDISPONÍVEL</p><h2>Configure o banco da oficina</h2><p>Não foi possível acessar o MySQL. Confira a conexão e importe <code>database/schema.sql</code> conforme o README.</p>
                <?php if ($requested === 'sair'): ?><form method="post" action="index.php?opcao=sair"><?php csrf_input(); ?><input type="hidden" name="acao" value="sair"><button type="submit">Encerrar sessão</button></form><?php endif; ?>
                </div>
            <?php else: require __DIR__ . '/views/' . $view . '.php'; endif; ?>
        </section>
    </main>
    <footer class="page-footer no-print"><span>Moto Oficina <span aria-hidden="true">·</span> Sistema de manutenção</span><span>PHP nativo &amp; MySQL</span></footer>
</div>
</body>
</html>
