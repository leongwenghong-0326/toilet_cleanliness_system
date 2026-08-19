<?php
session_start(); require_once __DIR__ . '/config.php';
if (user()) redirect(user()['role'] === 'admin' ? 'admin.php' : 'app.php');
$pdo = db(); $token = $_GET['token'] ?? $_POST['token'] ?? ''; $error = ''; $done = false;
$statement = $pdo->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
$statement->execute([$token]);
$reset = $statement->fetch();
if (!$reset) { $error = 'This reset link is invalid or has expired.'; }
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (strlen($_POST['password'] ?? '') < 6) { $error = 'Password must be at least 6 characters.'; }
    else {
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$reset['id']]);
        $done = true;
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset password · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body class="setup"><main class="setup-card"><div class="brand-mark">CC</div><p class="eyebrow">Account recovery</p><h1>Choose a new password</h1>
<?php if ($done): ?><div class="notice success">Password updated. <a href="index.php">Sign in →</a></div>
<?php elseif ($error): ?><div class="notice error"><?= e($error) ?></div>
<?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="token" value="<?= e($token) ?>"><label>New password<input type="password" name="password" minlength="6" required autofocus></label><button class="button primary wide" type="submit">Update password</button></form><?php endif; ?>
</main></body></html>
