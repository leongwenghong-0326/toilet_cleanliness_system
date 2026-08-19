<?php
function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) putenv("$key=$value");
    }
}
load_env(__DIR__ . '/.env');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'toilet_cleanliness');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
const APP_NAME = 'ClearCheck';
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;
const SESSION_OVERDUE_MINUTES = 120;
const ISSUE_KEYWORDS = ['dirty','wet','smell','odour','odor','rubbish','trash','broken','clog','leak','stain','overflow'];

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function csrf(): string { $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid request token.'); } }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) redirect('index.php'); }
function require_admin(): void { require_login(); if (user()['role'] !== 'admin') redirect('app.php'); }
function format_time(?string $time): string { return $time ? date('g:i A', strtotime($time)) : '—'; }
function format_date(?string $date): string { return $date ? date('d M Y', strtotime($date)) : '—'; }
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function is_login_locked(PDO $pdo, string $email): bool {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND succeeded = 0 AND attempted_at > (NOW() - INTERVAL ' . LOGIN_LOCKOUT_MINUTES . ' MINUTE)');
    $statement->execute([$email]);
    return (int) $statement->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_login_attempt(PDO $pdo, string $email, bool $succeeded): void {
    $statement = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (?,?,?)');
    $statement->execute([$email, client_ip(), $succeeded ? 1 : 0]);
}

function comment_flags_issue(string $comment): bool {
    $comment = mb_strtolower($comment);
    foreach (ISSUE_KEYWORDS as $keyword) { if (str_contains($comment, $keyword)) return true; }
    return false;
}

function is_session_overdue(string $checkInAt): bool {
    return (time() - strtotime($checkInAt)) > SESSION_OVERDUE_MINUTES * 60;
}
