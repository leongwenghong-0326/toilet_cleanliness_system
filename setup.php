<?php
session_start();
require_once __DIR__ . '/config.php';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = db();
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema))) as $statement) {
            if (!preg_match('/^CREATE DATABASE|^USE /i', $statement)) { $pdo->exec($statement); }
        }
        $users = [
            ['Campus Admin', 'admin@clearcheck.test', 'admin123', 'admin'],
            ['Ali Hassan', 'ali@student.test', 'student123', 'student'],
            ['Maya Joseph', 'maya@student.test', 'student123', 'student'],
        ];
        $insert = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash), role=VALUES(role), active=1');
        foreach ($users as $item) { $insert->execute([$item[0], $item[1], password_hash($item[2], PASSWORD_DEFAULT), $item[3]]); }
        $toilets = [
            ['T01', 'North Wing · Ground', 'North Wing', 'Ground floor', 'attention'],
            ['T02', 'Library · Level 1', 'Library', 'Level 1', 'available'],
            ['T03', 'Science Block · Level 2', 'Science Block', 'Level 2', 'available'],
            ['T04', 'Student Centre · Ground', 'Student Centre', 'Ground floor', 'available'],
        ];
        $insert = $pdo->prepare('INSERT INTO toilets (code,name,building,floor_label,status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), building=VALUES(building), floor_label=VALUES(floor_label), status=VALUES(status)');
        foreach ($toilets as $item) { $insert->execute($item); }
        $studentIds = $pdo->query("SELECT id FROM users WHERE role='student'")->fetchAll(PDO::FETCH_COLUMN);
        $toiletIds = $pdo->query('SELECT id FROM toilets')->fetchAll(PDO::FETCH_COLUMN);
        $link = $pdo->prepare('INSERT IGNORE INTO user_toilets (user_id,toilet_id) VALUES (?,?)');
        foreach ($studentIds as $studentId) foreach ($toiletIds as $toiletId) $link->execute([$studentId, $toiletId]);
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $message = 'Setup complete. Demo accounts are ready.';
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body class="setup"><main class="setup-card"><div class="brand-mark">CC</div><p class="eyebrow">Campus operations</p><h1>Set up ClearCheck</h1><p class="muted">Create the MySQL database, tables, demo users, and sample toilet locations.</p><?php if ($message): ?><div class="notice success"><?= e($message) ?><br><a href="index.php">Open the app →</a></div><?php endif; ?><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post"><button class="button primary" type="submit">Initialize database</button></form><p class="hint">Admin: admin@clearcheck.test / admin123<br>Student: ali@student.test / student123</p></main></body></html>
