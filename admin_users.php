<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin(); $pdo = db(); $error = ''; $flashMsg = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $insert = $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"student")');
            $insert->execute([trim($_POST['name']), trim($_POST['email']), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            flash('success', 'Student account created.');
        } elseif ($action === 'toggle_active') {
            $update = $pdo->prepare('UPDATE users SET active = NOT active WHERE id=? AND role="student"');
            $update->execute([(int) $_POST['id']]);
            flash('success', 'Account status updated.');
        } elseif ($action === 'assign') {
            $studentId = (int) $_POST['user_id'];
            $pdo->prepare('DELETE FROM user_toilets WHERE user_id=?')->execute([$studentId]);
            $insert = $pdo->prepare('INSERT IGNORE INTO user_toilets (user_id,toilet_id) VALUES (?,?)');
            foreach ($_POST['toilet_ids'] ?? [] as $toiletId) { $insert->execute([$studentId, (int) $toiletId]); }
            flash('success', 'Toilet assignments updated.');
        }
        redirect('admin_users.php');
    } catch (Throwable $exception) { $error = $exception->getCode() === '23000' ? 'That email is already registered.' : $exception->getMessage(); }
}
$students = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM user_toilets ut WHERE ut.user_id=u.id) assigned_count FROM users u WHERE role='student' ORDER BY u.name")->fetchAll();
$toilets = $pdo->query('SELECT id,code,name FROM toilets ORDER BY code')->fetchAll();
$assignments = $pdo->query('SELECT user_id, toilet_id FROM user_toilets')->fetchAll();
$assignmentMap = [];
foreach ($assignments as $row) { $assignmentMap[$row['user_id']][] = (int) $row['toilet_id']; }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage students · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="admin.php"><span class="brand-mark">CC</span><span>ClearCheck <small>ADMIN</small></span></a><div class="top-actions"><span class="user-chip"><span class="avatar">A</span><?= e(user()['name']) ?></span><a class="text-link" href="logout.php">Sign out</a></div></header><main class="page">
<nav class="admin-nav"><a href="admin.php">Overview</a><a href="admin_toilets.php">Toilets</a><a class="active" href="admin_users.php">Students</a></nav>
<div class="page-heading"><div><p class="eyebrow">Access control</p><h1>Manage students.</h1><p class="muted">Create accounts and assign toilets each student is responsible for.</p></div></div>
<?php if ($flashMsg): ?><div class="notice <?= e($flashMsg[0]) ?>"><?= e($flashMsg[1]) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="work-panel"><div><p class="eyebrow">New account</p><h2>Add a student</h2><p class="muted">They can sign in immediately with the password you set.</p></div>
<form method="post" class="visit-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="create">
<label>Full name<input name="name" required></label>
<label>Email<input type="email" name="email" required></label>
<label>Temporary password<input type="password" name="password" minlength="6" required></label>
<button class="button primary" type="submit">Create student <span>→</span></button></form></section>
<section class="history-section"><div class="section-title"><div><p class="eyebrow">All students</p><h2>Students &amp; toilet assignments</h2></div><span class="count-label"><?= count($students) ?> total</span></div>
<?php foreach ($students as $student): $assigned = $assignmentMap[$student['id']] ?? []; ?>
<div class="student-card"><div class="student-head"><div><strong><?= e($student['name']) ?></strong><small><?= e($student['email']) ?></small></div>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="<?= $student['id'] ?>"><button class="button outline" type="submit"><?= $student['active'] ? 'Deactivate' : 'Activate' ?></button></form></div>
<form method="post" class="assign-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="assign"><input type="hidden" name="user_id" value="<?= $student['id'] ?>">
<div class="toilet-checks"><?php foreach ($toilets as $toilet): ?><label class="check-pill"><input type="checkbox" name="toilet_ids[]" value="<?= $toilet['id'] ?>" <?= in_array($toilet['id'], $assigned, true) ? 'checked' : '' ?>><?= e($toilet['code']) ?></label><?php endforeach; ?></div>
<button class="button dark" type="submit">Save assignments</button></form></div>
<?php endforeach; if (!$students): ?><p class="muted">No students yet.</p><?php endif; ?>
</section></main></body></html>
