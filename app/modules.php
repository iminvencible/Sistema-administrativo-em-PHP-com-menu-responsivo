<?php
declare(strict_types=1);

function home_module(PDO $db, array $post): array
{
    return ['stats' => $db->query('SELECT (SELECT COUNT(*) FROM clientes) AS clientes, (SELECT COUNT(*) FROM produtos) AS produtos, (SELECT COUNT(*) FROM estoque WHERE quantidade < quantidade_minima) AS criticos, (SELECT COUNT(*) FROM ordens_servico WHERE status <> "concluida") AS ordens')->fetch()];
}

function clients_module(PDO $db, array $post): array
{
    if ($post) {
        $action = raw($post, 'acao');
        if ($action === 'excluir') {
            $statement = $db->prepare('DELETE FROM clientes WHERE id = ?');
            $statement->execute([integer_field($post, 'id', 'Cliente', 1)]);
            redirect('cliente', 'Cliente excluído.');
        }
        if ($action !== 'salvar') { throw new DomainException('Ação de cliente inválida.'); }
        $name = text_field($post, 'nome', 'Nome', 100);
        $cpf = preg_replace('/\D/', '', raw($post, 'cpf'));
        if (!valid_cpf($cpf)) { throw new DomainException('Informe um CPF válido.'); }
        $phone = text_field($post, 'telefone', 'Telefone', 20);
        if (!preg_match('/^\d{10,11}$/D', preg_replace('/\D/', '', $phone))) { throw new DomainException('Informe um telefone com DDD (10 ou 11 dígitos).'); }
        $email = text_field($post, 'email', 'E-mail', 120, false);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new DomainException('Informe um e-mail válido.'); }
        $model = text_field($post, 'modelo_moto', 'Modelo da moto', 80);
        $plate = strtoupper(str_replace('-', '', raw($post, 'placa_moto')));
        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/D', $plate)) { throw new DomainException('Informe uma placa válida (ABC1234 ou ABC1D23).'); }
        $values = [$name, $cpf, $phone, $email, $model, $plate];
        if (raw($post, 'id') !== '') {
            $values[] = integer_field($post, 'id', 'Cliente', 1);
            $statement = $db->prepare('UPDATE clientes SET nome=?, cpf=?, telefone=?, email=?, modelo_moto=?, placa_moto=? WHERE id=?');
        } else {
            $statement = $db->prepare('INSERT INTO clientes (nome, cpf, telefone, email, modelo_moto, placa_moto) VALUES (?, ?, ?, ?, ?, ?)');
        }
        $statement->execute($values);
        redirect('cliente', 'Cadastro de cliente salvo.');
    }
    $editId = max(0, (int) raw($_GET, 'editar'));
    $edit = $editId ? fetch_rows($db, 'SELECT * FROM clientes WHERE id=?', [$editId]) : [];
    return ['rows' => fetch_rows($db, 'SELECT * FROM clientes ORDER BY nome, id'), 'edit' => $edit[0] ?? []];
}

function products_module(PDO $db, array $post): array
{
    if ($post) {
        $action = raw($post, 'acao');
        if ($action === 'excluir') {
            $statement = $db->prepare('DELETE FROM produtos WHERE id=?');
            $statement->execute([integer_field($post, 'id', 'Produto', 1)]);
            redirect('produto', 'Produto excluído.');
        }
        if ($action !== 'salvar') { throw new DomainException('Ação de produto inválida.'); }
        $sku = text_field($post, 'sku', 'Código da peça (SKU)', 40);
        $name = text_field($post, 'nome', 'Nome da peça', 120);
        $category = text_field($post, 'categoria', 'Categoria', 60);
        $fit = text_field($post, 'compatibilidade', 'Compatibilidade', 100);
        $price = decimal(cents(raw($post, 'preco'), true));
        $minimum = integer_field($post, 'quantidade_minima', 'Estoque mínimo');
        $db->beginTransaction();
        if (raw($post, 'id') !== '') {
            $id = integer_field($post, 'id', 'Produto', 1);
            if (!fetch_rows($db, 'SELECT id FROM produtos WHERE id=? FOR UPDATE', [$id])) { throw new DomainException('Produto não encontrado.'); }
            $statement = $db->prepare('UPDATE produtos SET sku=?, nome=?, categoria=?, compatibilidade=?, preco=? WHERE id=?');
            $statement->execute([$sku, $name, $category, $fit, $price, $id]);
            $statement = $db->prepare('UPDATE estoque SET quantidade_minima=? WHERE produto_id=?');
            $statement->execute([$minimum, $id]);
        } else {
            $initial = integer_field($post, 'quantidade', 'Estoque inicial');
            $statement = $db->prepare('INSERT INTO produtos (sku, nome, categoria, compatibilidade, preco) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$sku, $name, $category, $fit, $price]);
            $id = (int) $db->lastInsertId();
            $statement = $db->prepare('INSERT INTO estoque (produto_id, quantidade, quantidade_minima) VALUES (?, ?, ?)');
            $statement->execute([$id, $initial, $minimum]);
            if ($initial > 0) {
                $statement = $db->prepare('INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo) VALUES (?, "entrada", ?, "Estoque inicial")');
                $statement->execute([$id, $initial]);
            }
        }
        $db->commit();
        redirect('produto', 'Peça e estoque mínimo salvos.');
    }
    $rows = fetch_rows($db, 'SELECT p.*, e.quantidade, e.quantidade_minima FROM produtos p JOIN estoque e ON e.produto_id=p.id ORDER BY p.nome');
    $editId = max(0, (int) raw($_GET, 'editar'));
    $edit = [];
    foreach ($rows as $row) { if ((int) $row['id'] === $editId) { $edit = $row; } }
    return ['rows' => $rows, 'edit' => $edit];
}

function stock_module(PDO $db, array $post): array
{
    if ($post) {
        if (raw($post, 'acao') !== 'movimentar') { throw new DomainException('Ação de estoque inválida.'); }
        $id = integer_field($post, 'produto_id', 'Produto', 1);
        $quantity = integer_field($post, 'quantidade', 'Quantidade', 1);
        $minimum = integer_field($post, 'quantidade_minima', 'Estoque mínimo');
        $reason = text_field($post, 'motivo', 'Motivo', 200);
        $type = raw($post, 'tipo');
        if (!in_array($type, ['entrada', 'saida'], true)) { throw new DomainException('Escolha entrada ou saída.'); }
        $db->beginTransaction();
        $rows = fetch_rows($db, 'SELECT * FROM estoque WHERE produto_id=? FOR UPDATE', [$id]);
        if (!$rows) { throw new DomainException('Produto não encontrado.'); }
        $new = (int) $rows[0]['quantidade'] + ($type === 'entrada' ? $quantity : -$quantity);
        if ($new < 0 || $new > 1000000) { throw new DomainException('Movimentação inválida: o saldo deve ficar entre 0 e 1.000.000.'); }
        $statement = $db->prepare('UPDATE estoque SET quantidade=?, quantidade_minima=? WHERE produto_id=?');
        $statement->execute([$new, $minimum, $id]);
        $statement = $db->prepare('INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo) VALUES (?, ?, ?, ?)');
        $statement->execute([$id, $type, $quantity, $reason]);
        $db->commit();
        redirect('estoque', 'Movimentação registrada.');
    }
    return [
        'rows' => fetch_rows($db, 'SELECT p.id, p.nome, p.sku, e.* FROM estoque e JOIN produtos p ON p.id=e.produto_id ORDER BY (e.quantidade < e.quantidade_minima) DESC, p.nome'),
        'movements' => fetch_rows($db, 'SELECT m.*, p.nome FROM movimentacoes_estoque m JOIN produtos p ON p.id=m.produto_id ORDER BY m.id DESC LIMIT 50'),
    ];
}

function create_order(PDO $db, array $post): void
{
    $clientId = integer_field($post, 'cliente_id', 'Cliente', 1);
    $description = text_field($post, 'descricao', 'Serviço solicitado', 500);
    $client = fetch_rows($db, 'SELECT * FROM clientes WHERE id=?', [$clientId]);
    if (!$client) { throw new DomainException('Cliente não encontrado.'); }
    $statement = $db->prepare('INSERT INTO ordens_servico (cliente_id, modelo_moto, placa_moto, descricao) VALUES (?, ?, ?, ?)');
    $statement->execute([$clientId, $client[0]['modelo_moto'], $client[0]['placa_moto'], $description]);
}

function register_sale(PDO $db, array $post): int
{
    $clientId = integer_field($post, 'cliente_id', 'Cliente', 1);
    $labor = cents(raw($post, 'mao_obra'));
    $service = text_field($post, 'servico', 'Serviço realizado', 500, false);
    $orderId = raw($post, 'ordem_servico_id') !== '' ? integer_field($post, 'ordem_servico_id', 'Ordem de serviço', 1) : null;
    $ids = $post['produto_id'] ?? [];
    $quantities = $post['quantidade'] ?? [];
    if (!is_array($ids) || !is_array($quantities) || count($ids) !== count($quantities) || count($ids) > 30) { throw new DomainException('Informe até 30 itens válidos.'); }
    $items = [];
    foreach ($ids as $key => $id) {
        if (!is_scalar($id) || !array_key_exists($key, $quantities) || !is_scalar($quantities[$key])) { throw new DomainException('Item inválido.'); }
        if (trim((string) $id) === '') { continue; }
        $productId = integer_field(['id' => $id], 'id', 'Produto', 1);
        $quantity = integer_field(['q' => $quantities[$key]], 'q', 'Quantidade', 1);
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        if ($items[$productId] > 1000000) { throw new DomainException('Quantidade de peças acima do limite.'); }
    }
    if (!$items && ($labor === 0 || $service === '')) { throw new DomainException('Inclua uma peça ou descreva um serviço com valor de mão de obra.'); }
    if ($labor > 0 && $service === '') { throw new DomainException('Descreva o serviço referente à mão de obra.'); }
    // Venda, itens, estoque e OS são gravados na mesma transação.
    $db->beginTransaction();
    $clientRows = fetch_rows($db, 'SELECT * FROM clientes WHERE id=? FOR UPDATE', [$clientId]);
    if (!$clientRows) { throw new DomainException('Cliente não encontrado.'); }
    $client = $clientRows[0];
    $model = $client['modelo_moto'];
    $plate = $client['placa_moto'];
    if ($orderId !== null) {
        $orders = fetch_rows($db, 'SELECT * FROM ordens_servico WHERE id=? FOR UPDATE', [$orderId]);
        if (!$orders || (int) $orders[0]['cliente_id'] !== $clientId || $orders[0]['status'] === 'concluida') { throw new DomainException('Escolha uma OS aberta deste cliente.'); }
        $model = $orders[0]['modelo_moto'];
        $plate = $orders[0]['placa_moto'];
    }
    ksort($items); // Evita bloqueios em ordem diferente entre vendas concorrentes.
    $details = [];
    $total = $labor;
    foreach ($items as $id => $quantity) {
        $products = fetch_rows($db, 'SELECT * FROM produtos WHERE id=? FOR UPDATE', [$id]);
        $stock = fetch_rows($db, 'SELECT quantidade FROM estoque WHERE produto_id=? FOR UPDATE', [$id]);
        if (!$products || !$stock || (int) $stock[0]['quantidade'] < $quantity) { throw new DomainException('Estoque insuficiente ou peça inexistente. Nenhuma venda foi registrada.'); }
        $product = $products[0];
        $total += cents($product['preco'], true) * $quantity;
        if ($total > 999999999999) { throw new DomainException('Total da venda acima do limite permitido.'); }
        $details[] = [$id, $product['nome'], $quantity, $product['preco']];
    }
    $statement = $db->prepare('INSERT INTO vendas (cliente_id, ordem_servico_id, cliente_nome, cliente_cpf, modelo_moto, placa_moto, servico, mao_obra, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([$clientId, $orderId, $client['nome'], $client['cpf'], $model, $plate, $service, decimal($labor), decimal($total)]);
    $saleId = (int) $db->lastInsertId();
    foreach ($details as [$id, $name, $quantity, $price]) {
        $statement = $db->prepare('INSERT INTO venda_itens (venda_id, produto_id, produto_nome, quantidade, preco_unitario) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([$saleId, $id, $name, $quantity, $price]);
        $statement = $db->prepare('UPDATE estoque SET quantidade=quantidade-? WHERE produto_id=?');
        $statement->execute([$quantity, $id]);
        $statement = $db->prepare('INSERT INTO movimentacoes_estoque (produto_id, venda_id, tipo, quantidade, motivo) VALUES (?, ?, "venda", ?, "Venda de peça")');
        $statement->execute([$id, $saleId, $quantity]);
    }
    if ($orderId !== null) {
        $statement = $db->prepare('UPDATE ordens_servico SET status="concluida" WHERE id=?');
        $statement->execute([$orderId]);
    }
    $db->commit();
    return $saleId;
}

function sales_module(PDO $db, array $post): array
{
    if ($post) {
        $action = raw($post, 'acao');
        if ($action === 'abrir_os') { create_order($db, $post); redirect('vendas', 'Ordem de serviço aberta.'); }
        if ($action === 'iniciar_os') {
            $statement = $db->prepare('UPDATE ordens_servico SET status="em_andamento" WHERE id=? AND status="aberta"');
            $statement->execute([integer_field($post, 'id', 'Ordem de serviço', 1)]);
            redirect('vendas', 'Ordem de serviço atualizada.');
        }
        if ($action !== 'vender') { throw new DomainException('Ação de vendas inválida.'); }
        $id = register_sale($db, $post);
        redirect('vendas', 'Venda #' . $id . ' registrada e estoque atualizado.');
    }
    $start = raw($_GET, 'inicio') ?: date('Y-m-01');
    $end = raw($_GET, 'fim') ?: date('Y-m-d');
    $filterError = '';
    try {
        date_filter($start); date_filter($end);
        if ($start > $end) { throw new DomainException('A data inicial deve ser anterior ou igual à final.'); }
    } catch (DomainException $e) {
        $filterError = $e->getMessage(); $start = date('Y-m-01'); $end = date('Y-m-d');
    }
    $endExclusive = (new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d');
    $params = [$start . ' 00:00:00', $endExclusive . ' 00:00:00'];
    $sales = fetch_rows($db, 'SELECT * FROM vendas WHERE criado_em >= ? AND criado_em < ? ORDER BY criado_em DESC, id DESC', $params);
    $items = fetch_rows($db, 'SELECT i.* FROM venda_itens i JOIN vendas v ON v.id=i.venda_id WHERE v.criado_em >= ? AND v.criado_em < ? ORDER BY i.id', $params);
    $bySale = [];
    foreach ($items as $item) { $bySale[$item['venda_id']][] = $item; }
    $total = 0;
    foreach ($sales as $sale) { $total += (int) str_replace('.', '', $sale['total']); }
    return [
        'sales' => $sales, 'items' => $bySale, 'total' => decimal($total), 'start' => $start, 'end' => $end, 'filter_error' => $filterError,
        'clients' => fetch_rows($db, 'SELECT * FROM clientes ORDER BY nome'),
        'products' => fetch_rows($db, 'SELECT p.*, e.quantidade FROM produtos p JOIN estoque e ON e.produto_id=p.id ORDER BY p.nome'),
        'orders' => fetch_rows($db, 'SELECT o.*, c.nome, c.cpf FROM ordens_servico o JOIN clientes c ON c.id=o.cliente_id ORDER BY o.id DESC'),
    ];
}

function settings_module(PDO $db, array $post): array
{
    if ($post) {
        if (raw($post, 'acao') !== 'preferencias') { throw new DomainException('Ação de configuração inválida.'); }
        $theme = raw($post, 'tema');
        if (!in_array($theme, ['claro', 'escuro'], true)) { throw new DomainException('Escolha um tema válido.'); }
        $large = raw($post, 'fonte_ampliada') === '1';
        $statement = $db->prepare('INSERT INTO configuracoes (chave_sessao, tema, fonte_ampliada) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE tema=VALUES(tema), fonte_ampliada=VALUES(fonte_ampliada)');
        $statement->execute([session_key(), $theme, (int) $large]);
        $_SESSION['tema'] = $theme; $_SESSION['fonte_ampliada'] = $large;
        redirect('configuracoes', 'Preferências salvas nesta sessão.');
    }
    return ['preferences' => fetch_rows($db, 'SELECT tema, fonte_ampliada FROM configuracoes WHERE chave_sessao=?', [session_key()])];
}

function end_session(?PDO $db): void
{
    if ($db) {
        try {
            $statement = $db->prepare('UPDATE sessoes SET encerrada_em=CURRENT_TIMESTAMP WHERE chave_sessao=?');
            $statement->execute([session_key()]);
        } catch (PDOException $e) { error_log('Não foi possível registrar a saída no banco: ' . $e->getCode()); }
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => 'Lax']);
    }
    session_destroy();
    header('Location: index.php?opcao=encerrado', true, 303);
    exit;
}

function logout_module(PDO $db, array $post): array
{
    if ($post) {
        if (raw($post, 'acao') !== 'sair') { throw new DomainException('Ação de saída inválida.'); }
        end_session($db);
    }
    return ['session' => fetch_rows($db, 'SELECT iniciada_em FROM sessoes WHERE chave_sessao=?', [session_key()])];
}
