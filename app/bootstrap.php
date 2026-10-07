<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");

function db(): PDO
{
    $local = __DIR__ . '/../config/database.local.php';
    $config = is_file($local) ? require $local : [];
    $env = static function (string $key, string $fallback) use ($config): string {
        $value = getenv('DB_' . strtoupper($key));
        return $value !== false ? $value : (string) ($config[$key] ?? $fallback);
    };
    $pdo = new PDO(
        'mysql:host=' . $env('host', '127.0.0.1') . ';port=' . $env('port', '3306') . ';dbname=' . $env('name', 'oficina_motos') . ';charset=utf8mb4',
        $env('user', 'root'),
        $env('password', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    // TIMESTAMP e filtros usam o mesmo fuso da oficina (São Paulo).
    $pdo->exec("SET time_zone = '-03:00'");
    return $pdo;
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function raw(array $input, string $key): string
{
    return isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';
}

function text_field(array $input, string $key, string $label, int $max, bool $required = true): string
{
    $value = raw($input, $key);
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) {
        throw new DomainException($label . ': informe um valor com até ' . $max . ' caracteres.');
    }
    return $value;
}

function integer_field(array $input, string $key, string $label, int $min = 0, int $max = 1000000): int
{
    $value = filter_var(raw($input, $key), FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new DomainException($label . ': informe um número inteiro entre ' . $min . ' e ' . $max . '.');
    }
    return $value;
}

// Dinheiro é calculado em centavos, sem erros de arredondamento de float.
function cents(string $value, bool $positive = false): int
{
    $value = str_replace(',', '.', $value);
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D', $value)) {
        throw new DomainException('Informe um valor monetário válido, sem separador de milhares.');
    }
    $parts = explode('.', $value);
    $result = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    if ($positive && $result === 0) {
        throw new DomainException('O preço deve ser maior que zero.');
    }
    return $result;
}

function decimal(int $value): string
{
    return intdiv($value, 100) . '.' . str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
}

function money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function valid_cpf(string $cpf): bool
{
    if (!preg_match('/^\d{11}$/D', $cpf) || preg_match('/^(\d)\1{10}$/D', $cpf)) {
        return false;
    }
    for ($length = 9; $length < 11; $length++) {
        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += (int) $cpf[$i] * ($length + 1 - $i);
        }
        $digit = ($sum * 10) % 11;
        if (($digit === 10 ? 0 : $digit) !== (int) $cpf[$length]) {
            return false;
        }
    }
    return true;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_input(): void
{
    echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function redirect(string $route, string $message): void
{
    $_SESSION['flash'] = $message;
    header('Location: index.php?opcao=' . urlencode($route), true, 303);
    exit;
}

function session_key(): string
{
    return hash('sha256', session_id());
}

function track_session(PDO $db): void
{
    $statement = $db->prepare('INSERT INTO sessoes (chave_sessao) VALUES (?) ON DUPLICATE KEY UPDATE ultima_atividade = CURRENT_TIMESTAMP');
    $statement->execute([session_key()]);
    $statement = $db->prepare('SELECT tema, fonte_ampliada FROM configuracoes WHERE chave_sessao = ?');
    $statement->execute([session_key()]);
    $preferences = $statement->fetch();
    if ($preferences) {
        $_SESSION['tema'] = $preferences['tema'];
        $_SESSION['fonte_ampliada'] = (bool) $preferences['fonte_ampliada'];
    }
}

function fetch_rows(PDO $db, string $sql, array $params = []): array
{
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function date_filter(string $value): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || substr($value, 0, 4) < '1000' || substr($value, 0, 4) > '9998') {
        throw new DomainException('Informe uma data válida.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new DomainException('Informe uma data válida.');
    }
    return $value;
}
