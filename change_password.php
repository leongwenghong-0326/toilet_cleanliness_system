<?php
session_start(); require_once __DIR__ . '/config.php'; require_login();
$pdo = db(); $error = ''; $flashMsg = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $current = trim($_POST['current_password'] ?? '');
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
        $statement->execute([user()['id']]);
        $account = $statement->fetch();
        if (!$account || !password_verify($current, $account['password_hash'])) throw new RuntimeException('Current password is incorrect.');
        if (strlen($new) < 6) throw new RuntimeException('New password must be at least 6 characters.');
        if ($new !== $confirm) throw new RuntimeException('New passwords do not match.');
        $update = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), user()['id']]);
        flash('success', 'Your password has been changed.');
        redirect(user()['role'] === 'admin' ? 'admin.php' : 'app.php');
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
$back = user()['role'] === 'admin' ? 'admin.php' : 'app.php';
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change password · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="<?= $back ?>"><span class="brand-mark">CC</span><span>ClearCheck</span></a><div class="top-actions"><span class="user-chip"><span class="avatar"><?= e(strtoupper(substr(user()['name'], 0, 1))) ?></span><?= e(user()['name']) ?></span><a class="text-link" href="logout.php">Sign out</a></div></header><main class="page"><div class="page-heading"><div><p class="eyebrow">Account settings</p><h1>Change your password.</h1><p class="muted">Choose a password you will remember. It must be at least 6 characters.</p></div></div><section class="settings-panel"><form method="post" class="visit-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Current password<input type="password" name="current_password" required autofocus></label><label>New password<input type="password" name="new_password" minlength="6" required></label><label>Confirm new password<input type="password" name="confirm_password" minlength="6" required></label><div class="settings-actions"><a class="button outline" href="<?= $back ?>">Cancel</a><button class="button primary" type="submit">Change password <span>→</span></button></div></form><?php if ($flashMsg): ?><div class="notice <?= e($flashMsg[0]) ?>"><?= e($flashMsg[1]) ?></div><?php endif; ?><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?></section></main></body></html>
