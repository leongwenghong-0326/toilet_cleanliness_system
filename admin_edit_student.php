<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin(); $pdo = db(); $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0); $error = '';
$lookup = $pdo->prepare('SELECT * FROM users WHERE id=? AND role="student" LIMIT 1'); $lookup->execute([$id]); $student = $lookup->fetch();
if (!$student) { flash('error', 'Student account was not found.'); redirect('admin_users.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a name and valid email address.');
        $update = $pdo->prepare('UPDATE users SET name=?,email=?,active=? WHERE id=? AND role="student"');
        $update->execute([$name, $email, isset($_POST['active']) ? 1 : 0, $id]);
        flash('success', 'Student account updated.'); redirect('admin_users.php');
    } catch (Throwable $exception) { $error = $exception->getCode() === '23000' ? 'That email is already registered.' : $exception->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit student · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="admin.php"><span class="brand-mark">CC</span><span>ClearCheck <small>ADMIN</small></span></a></header><main class="page"><div class="page-heading"><div><p class="eyebrow">Student account</p><h1>Edit <?= e($student['name']) ?>.</h1><p class="muted">Update account details and access status.</p></div></div><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><section class="settings-panel"><form method="post" class="visit-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $student['id'] ?>"><label>Full name<input name="name" value="<?= e($student['name']) ?>" required></label><label>Email<input type="email" name="email" value="<?= e($student['email']) ?>" required></label><label class="check-pill"><input type="checkbox" name="active" <?= $student['active'] ? 'checked' : '' ?>> Account is active</label><div class="settings-actions"><a class="button outline" href="admin_users.php">Cancel</a><button class="button primary" type="submit">Save student <span>→</span></button></div></form></section></main></body></html>
