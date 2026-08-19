<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'toilet_cleanliness');
define('DB_USER', 'root');
define('DB_PASS', 'Hong2007');
const APP_NAME = 'ClearCheck';
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;
const SESSION_OVERDUE_MINUTES = 120;
const ISSUE_KEYWORDS = ['dirty','wet','smell','odour','odor','rubbish','trash','broken','clog','leak','stain','overflow'];

function initialize_database(PDO $pdo): void {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to read schema.sql.');
    }

    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema))) as $statement) {
        if (preg_match('/^CREATE DATABASE|^USE /i', $statement)) {
            continue;
        }
        $pdo->exec($statement);
    }

    $users = [
        ['Campus Admin', 'admin@clearcheck.test', 'admin123', 'admin'],
        ['Ali Hassan', 'ali@student.test', 'student123', 'student'],
        ['Maya Joseph', 'maya@student.test', 'student123', 'student'],
    ];
    $insert = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash), role=VALUES(role), active=1');
    foreach ($users as $user) {
        $insert->execute([$user[0], $user[1], password_hash($user[2], PASSWORD_DEFAULT), $user[3]]);
    }

    $toilets = [
        ['T01', 'North Wing · Ground', 'North Wing', 'Ground floor', 'attention'],
        ['T02', 'Library · Level 1', 'Library', 'Level 1', 'available'],
        ['T03', 'Science Block · Level 2', 'Science Block', 'Level 2', 'available'],
        ['T04', 'Student Centre · Ground', 'Student Centre', 'Ground floor', 'available'],
    ];
    $insert = $pdo->prepare('INSERT INTO toilets (code,name,building,floor_label,status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), building=VALUES(building), floor_label=VALUES(floor_label), status=VALUES(status)');
    foreach ($toilets as $toilet) {
        $insert->execute($toilet);
    }

    $studentIds = $pdo->query("SELECT id FROM users WHERE role='student'")->fetchAll(PDO::FETCH_COLUMN);
    $toiletIds = $pdo->query('SELECT id FROM toilets')->fetchAll(PDO::FETCH_COLUMN);
    $link = $pdo->prepare('INSERT IGNORE INTO user_toilets (user_id,toilet_id) VALUES (?,?)');
    foreach ($studentIds as $studentId) {
        foreach ($toiletIds as $toiletId) {
            $link->execute([$studentId, $toiletId]);
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
}

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $hasUsersTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
        if (!$hasUsersTable) {
            initialize_database($pdo);
        }
    } catch (Throwable $exception) {
        $bootstrap = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        initialize_database($pdo);
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
