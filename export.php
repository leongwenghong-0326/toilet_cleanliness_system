<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin(); $pdo = db();
$where = []; $params = [];
if (!empty($_GET['toilet'])) { $where[] = 's.toilet_id = ?'; $params[] = (int) $_GET['toilet']; }
if (!empty($_GET['status'])) { $where[] = 's.status = ?'; $params[] = $_GET['status']; }
if (!empty($_GET['date'])) { $where[] = 'DATE(s.check_in_at) = ?'; $params[] = $_GET['date']; }
if (!empty($_GET['student'])) { $where[] = 'u.name LIKE ?'; $params[] = '%' . $_GET['student'] . '%'; }
$sql = "SELECT s.*,u.name user_name,t.code,t.name toilet_name FROM toilet_sessions s JOIN users u ON u.id=s.user_id JOIN toilets t ON t.id=s.toilet_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY s.check_in_at DESC';
$statement = $pdo->prepare($sql); $statement->execute($params); $rows = $statement->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="toilet-history-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Student', 'Toilet code', 'Toilet name', 'Check-in', 'Check-in comment', 'Check-out', 'Check-out comment', 'Status']);
foreach ($rows as $row) {
    fputcsv($out, [$row['user_name'], $row['code'], $row['toilet_name'], $row['check_in_at'], $row['check_in_comment'], $row['check_out_at'] ?? '', $row['check_out_comment'] ?? '', $row['status']]);
}
fclose($out);
