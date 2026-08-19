<?php
session_start();
require_once __DIR__ . '/config.php';
if (user()) redirect(user()['role'] === 'admin' ? 'admin.php' : 'app.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    try {
        $pdo = db();
        if (is_login_locked($pdo, $email)) {
            $error = 'Too many failed attempts. Try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
        } else {
            $statement = $pdo->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email = ? AND active = 1 LIMIT 1');
            $statement->execute([$email]);
            $account = $statement->fetch();
            $success = $account && password_verify($_POST['password'] ?? '', $account['password_hash']);
            record_login_attempt($pdo, $email, (bool) $success);
            if ($success) {
                unset($account['password_hash']); $_SESSION['user'] = $account;
                redirect($account['role'] === 'admin' ? 'admin.php' : 'app.php');
            }
            $error = 'That email and password do not match an active account.';
        }
    } catch (Throwable $exception) { $error = 'Database is not ready. Run setup.php first.'; }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body class="login-page"><div class="login-shell"><section class="login-intro"><a class="brand" href="index.php"><span class="brand-mark">CC</span><span>ClearCheck</span></a><div class="intro-copy"><p class="eyebrow">Campus cleanliness, made accountable</p><h1>Every clean room has a <em>clear record.</em></h1><p>Capture the moment before and after every visit. Keep shared spaces welcoming, one check-in at a time.</p></div><div class="intro-footer"><span class="live-dot"></span> Live monitoring across campus</div></section><section class="login-panel"><div class="login-form"><p class="eyebrow">Welcome back</p><h2>Sign in to your workspace</h2><p class="muted">Use your college account to continue.</p><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post"><label>Email address<input type="email" name="email" placeholder="you@college.edu" required autofocus></label><label>Password<div class="password-field"><input id="password" type="password" name="password" placeholder="••••••••" required><button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">Show</button></div></label><button class="button primary wide" type="submit">Sign in <span>→</span></button></form><script>document.getElementById('togglePassword').addEventListener('click', function () { const input = document.getElementById('password'); const isHidden = input.type === 'password'; input.type = isHidden ? 'text' : 'password'; this.textContent = isHidden ? 'Hide' : 'Show'; this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password'); });</script><p class="hint"><a class="text-link" href="forgot.php">Forgot password?</a></p><p class="hint">Demo admin: admin@clearcheck.test / admin123<br>Demo student: ali@student.test / student123</p></div></section></div></body></html>
