<?php
session_start(); require_once __DIR__ . '/config.php';
if (user()) redirect(user()['role'] === 'admin' ? 'admin.php' : 'app.php');
$message = ''; $error = ''; $resetLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $pdo = db();
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? AND active = 1 LIMIT 1');
        $statement->execute([trim($_POST['email'] ?? '')]);
        $account = $statement->fetch();
        $message = 'If that email exists, a reset link has been generated.';
        if ($account) {
            $token = bin2hex(random_bytes(32));
            $insert = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?, NOW() + INTERVAL 30 MINUTE)');
            $insert->execute([$account['id'], $token]);
            $resetLink = 'reset.php?token=' . $token;
        }
    } catch (Throwable $exception) { $error = 'Database is not ready. Run setup.php first.'; }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Forgot password · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body class="setup"><main class="setup-card"><div class="brand-mark">CC</div><p class="eyebrow">Account recovery</p><h1>Reset your password</h1><p class="muted">Enter your email and we will generate a reset link.</p>
<?php if ($message): ?><div class="notice success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<?php if ($resetLink): ?><div class="notice success">No mail server is configured in this environment, so here is your one-time link:<br><a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Email address<input type="email" name="email" required autofocus></label><button class="button primary wide" type="submit">Send reset link</button></form>
<p class="hint"><a class="text-link" href="index.php">Back to sign in</a></p></main></body></html>
