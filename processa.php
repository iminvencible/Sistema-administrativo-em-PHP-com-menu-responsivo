<?php
// Compatibilidade com o menu original. O único roteador fica em index.php.
// Esta ponte não realiza alterações: até F só abre a confirmação.
$aliases = ['A' => 'cliente', 'B' => 'produto', 'C' => 'vendas', 'D' => 'estoque', 'E' => 'configuracoes', 'F' => 'sair'];
$value = $_POST['opcao'] ?? $_GET['opcao'] ?? '';
$value = is_string($value) ? strtoupper(trim($value)) : '';
header('Location: index.php?opcao=' . ($aliases[$value] ?? 'inicio'), true, 303);
exit;
