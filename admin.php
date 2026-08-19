<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin(); $pdo = db();
$stats = ['toilets'=>(int)$pdo->query('SELECT COUNT(*) FROM toilets')->fetchColumn(),'today'=>(int)$pdo->query("SELECT COUNT(*) FROM toilet_sessions WHERE DATE(check_in_at)=CURDATE()")->fetchColumn(),'active'=>(int)$pdo->query("SELECT COUNT(*) FROM toilet_sessions WHERE status='active'")->fetchColumn(),'students'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND active=1")->fetchColumn()];

$toiletFilter = $_GET['toilet'] ?? ''; $statusFilter = $_GET['status'] ?? ''; $dateFilter = $_GET['date'] ?? ''; $studentFilter = trim($_GET['student'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1)); $perPage = 10; $offset = ($page - 1) * $perPage;

$where = []; $params = [];
if ($toiletFilter !== '') { $where[] = 's.toilet_id = ?'; $params[] = (int) $toiletFilter; }
if ($statusFilter !== '') { $where[] = 's.status = ?'; $params[] = $statusFilter; }
if ($dateFilter !== '') { $where[] = 'DATE(s.check_in_at) = ?'; $params[] = $dateFilter; }
if ($studentFilter !== '') { $where[] = 'u.name LIKE ?'; $params[] = '%' . $studentFilter . '%'; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM toilet_sessions s JOIN users u ON u.id=s.user_id JOIN toilets t ON t.id=s.toilet_id$whereSql");
$countStatement->execute($params); $totalRows = (int) $countStatement->fetchColumn(); $totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "SELECT s.*,u.name user_name,t.code,t.name toilet_name,(SELECT COUNT(*) FROM session_photos p WHERE p.session_id=s.id AND p.phase='before') before_count,(SELECT COUNT(*) FROM session_photos p WHERE p.session_id=s.id AND p.phase='after') after_count FROM toilet_sessions s JOIN users u ON u.id=s.user_id JOIN toilets t ON t.id=s.toilet_id$whereSql ORDER BY s.check_in_at DESC LIMIT $perPage OFFSET $offset";
$statement = $pdo->prepare($sql); $statement->execute($params); $rows = $statement->fetchAll();
$toilets = $pdo->query('SELECT id,code,name FROM toilets ORDER BY code')->fetchAll();
$queryWithout = fn(string $key) => http_build_query(array_diff_key($_GET, [$key => '', 'page' => '']));
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin overview · ClearCheck</title><link rel="stylesheet" href="assets/style.css"><script defer src="assets/lightbox.js"></script></head><body><header class="topbar"><a class="brand" href="admin.php"><span class="brand-mark">CC</span><span>ClearCheck <small>ADMIN</small></span></a><div class="top-actions"><span class="user-chip"><span class="avatar">A</span><?= e(user()['name']) ?></span><a class="text-link" href="logout.php">Sign out</a></div></header><main class="page">
<nav class="admin-nav"><a class="active" href="admin.php">Overview</a><a href="admin_toilets.php">Toilets</a><a href="admin_users.php">Students</a></nav>
<div class="page-heading"><div><p class="eyebrow">Operations overview</p><h1>Campus at a glance.</h1><p class="muted">A live record of cleanliness, responsibility, and response.</p></div><div class="date-stamp">Today<strong><?= date('d M Y') ?></strong></div></div>
<div class="stats-grid"><div class="stat-card"><span>Assigned toilets</span><strong><?= $stats['toilets'] ?></strong><small>Across campus</small></div><div class="stat-card accent"><span>Visits today</span><strong><?= $stats['today'] ?></strong><small>New records</small></div><div class="stat-card"><span>Active now</span><strong><?= $stats['active'] ?></strong><small>In progress</small></div><div class="stat-card"><span>Active students</span><strong><?= $stats['students'] ?></strong><small>With access</small></div></div>
<section class="history-section admin-history"><div class="section-title"><div><p class="eyebrow">Accountability log</p><h2>Shared toilet history</h2></div><a class="text-link" href="export.php?<?= e(http_build_query($_GET)) ?>">Export CSV ↓</a></div>
<form method="get" class="filter-bar">
<select name="toilet"><option value="">All toilets</option><?php foreach ($toilets as $toilet): ?><option value="<?= $toilet['id'] ?>" <?= $toiletFilter == $toilet['id'] ? 'selected' : '' ?>><?= e($toilet['code']) ?> · <?= e($toilet['name']) ?></option><?php endforeach; ?></select>
<select name="status"><option value="">Any status</option><option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option><option value="completed" <?= $statusFilter==='completed'?'selected':'' ?>>Completed</option></select>
<input type="date" name="date" value="<?= e($dateFilter) ?>">
<input type="text" name="student" placeholder="Student name" value="<?= e($studentFilter) ?>">
<button class="button outline" type="submit">Filter</button><a class="button outline" href="admin.php">Reset</a>
</form>
<div class="table-wrap"><table><thead><tr><th>Visit</th><th>Toilet</th><th>Check-in</th><th>Comments</th><th>Evidence</th><th>State</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): $overdue = $row['status'] === 'active' && is_session_overdue($row['check_in_at']); ?>
<tr><td><strong><?= e($row['user_name']) ?></strong><small><?= format_date($row['check_in_at']) ?></small></td>
<td><strong><?= e($row['code']) ?></strong><small><?= e($row['toilet_name']) ?></small></td>
<td><strong><?= format_time($row['check_in_at']) ?></strong><small><?= $row['check_out_at'] ? 'Out ' . format_time($row['check_out_at']) : 'Still active' ?></small></td>
<td class="comment-cell"><small><b>In:</b> <?= e($row['check_in_comment']) ?></small><small><b>Out:</b> <?= $row['check_out_comment'] ? e($row['check_out_comment']) : '—' ?></small></td>
<td><span class="evidence"><span>Before <?= $row['before_count'] ?></span><span>After <?= $row['after_count'] ?></span></span></td>
<td><span class="history-state <?= $row['status']==='completed'?'done':'live' ?>"><?= ucfirst($row['status']) ?></span><?php if ($overdue): ?><span class="history-state overdue">Overdue</span><?php endif; ?></td>
<td><button type="button" class="text-link" data-view-photos="<?= $row['id'] ?>">View photos</button></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">No visits match these filters.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($totalPages > 1): ?><div class="pagination">
<?php for ($p = 1; $p <= $totalPages; $p++): ?><a class="<?= $p === $page ? 'active' : '' ?>" href="?<?= e($queryWithout('page')) ?>&page=<?= $p ?>"><?= $p ?></a><?php endfor; ?>
</div><?php endif; ?>
</section>
<section class="admin-note"><span class="shield">✓</span><div><strong>History is protected</strong><p>Completed visits are retained as immutable records. Only active visits can be checked out.</p></div></section>
</main></body></html>
