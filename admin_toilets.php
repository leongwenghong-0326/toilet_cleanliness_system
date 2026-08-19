<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin(); $pdo = db(); $error = ''; $flashMsg = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $insert = $pdo->prepare('INSERT INTO toilets (code,name,building,floor_label,status) VALUES (?,?,?,?,?)');
            $insert->execute([trim($_POST['code']), trim($_POST['name']), trim($_POST['building']), trim($_POST['floor_label']), $_POST['status']]);
            flash('success', 'Toilet added.');
        } elseif ($action === 'update') {
            $update = $pdo->prepare('UPDATE toilets SET code=?,name=?,building=?,floor_label=?,status=? WHERE id=?');
            $update->execute([trim($_POST['code']), trim($_POST['name']), trim($_POST['building']), trim($_POST['floor_label']), $_POST['status'], (int) $_POST['id']]);
            flash('success', 'Toilet updated.');
        } elseif ($action === 'set_status') {
            $update = $pdo->prepare('UPDATE toilets SET status=? WHERE id=?');
            $update->execute([$_POST['status'], (int) $_POST['id']]);
            flash('success', 'Status updated.');
        } elseif ($action === 'delete') {
            $toiletId = (int) $_POST['id'];
            $history = $pdo->prepare('SELECT COUNT(*) FROM toilet_sessions WHERE toilet_id=?');
            $history->execute([$toiletId]);
            if ((int) $history->fetchColumn() > 0) throw new RuntimeException('This toilet has visit history and cannot be deleted. Mark it closed to preserve accountability records.');
            $delete = $pdo->prepare('DELETE FROM toilets WHERE id=?');
            $delete->execute([$toiletId]);
            flash('success', 'Toilet deleted.');
        }
        redirect('admin_toilets.php');
    } catch (Throwable $exception) { $error = $exception->getCode() === '23000' ? 'That toilet code is already in use.' : $exception->getMessage(); }
}
$toilets = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM toilet_sessions s WHERE s.toilet_id=t.id) visit_count, (SELECT COUNT(*) FROM user_toilets ut WHERE ut.toilet_id=t.id) assigned_count FROM toilets t ORDER BY t.code")->fetchAll();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage toilets · ClearCheck</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="admin.php"><span class="brand-mark">CC</span><span>ClearCheck <small>ADMIN</small></span></a><div class="top-actions"><span class="user-chip"><span class="avatar">A</span><?= e(user()['name']) ?></span><a class="text-link" href="logout.php">Sign out</a></div></header><main class="page">
<nav class="admin-nav"><a href="admin.php">Overview</a><a class="active" href="admin_toilets.php">Toilets</a><a href="admin_users.php">Students</a></nav>
<div class="page-heading"><div><p class="eyebrow">Facilities</p><h1>Manage toilets.</h1><p class="muted">Add locations and control their availability.</p></div></div>
<?php if ($flashMsg): ?><div class="notice <?= e($flashMsg[0]) ?>"><?= e($flashMsg[1]) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="work-panel"><div><p class="eyebrow">New location</p><h2>Add a toilet</h2><p class="muted">Codes must be unique across campus.</p></div>
<form method="post" class="visit-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="create">
<label>Code<input name="code" placeholder="T05" maxlength="20" required></label>
<label>Name<input name="name" placeholder="Engineering Block · Level 1" required></label>
<label>Building<input name="building" placeholder="Engineering Block" required></label>
<label>Floor<input name="floor_label" placeholder="Level 1" required></label>
<label>Status<select name="status"><option value="available">Available</option><option value="attention">Needs attention</option><option value="closed">Closed</option></select></label>
<button class="button primary" type="submit">Add toilet <span>→</span></button></form></section>
<section class="history-section"><div class="section-title"><div><p class="eyebrow">All locations</p><h2>Toilets</h2></div><span class="count-label"><?= count($toilets) ?> total</span></div>
<div class="table-wrap"><table><thead><tr><th>Code</th><th>Location</th><th>Assigned</th><th>Visits</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($toilets as $toilet): ?><tr><td><strong><?= e($toilet['code']) ?></strong></td><td><strong><?= e($toilet['name']) ?></strong><small><?= e($toilet['building']) ?> · <?= e($toilet['floor_label']) ?></small></td><td><?= (int) $toilet['assigned_count'] ?> students</td><td><?= (int) $toilet['visit_count'] ?></td><td><span class="status <?= e($toilet['status']) ?>"><?= e(ucfirst($toilet['status'])) ?></span></td><td class="row-actions">
<div class="row-actions"><a class="button outline" href="admin_edit_toilet.php?id=<?= $toilet['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $toilet['id'] ?>"><select name="status" onchange="this.form.submit()"><option value="available" <?= $toilet['status']==='available'?'selected':'' ?>>Available</option><option value="attention" <?= $toilet['status']==='attention'?'selected':'' ?>>Needs attention</option><option value="closed" <?= $toilet['status']==='closed'?'selected':'' ?>>Closed</option></select></form><form method="post" onsubmit="return confirm('Delete this toilet?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $toilet['id'] ?>"><button class="button danger-button" type="submit">Delete</button></form></div>
</td></tr><?php endforeach; if (!$toilets): ?><tr><td colspan="6" class="muted">No toilets yet.</td></tr><?php endif; ?>
</tbody></table></div></section></main></body></html>
