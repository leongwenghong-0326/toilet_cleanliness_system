<?php
session_start(); require_once __DIR__ . '/config.php'; require_login();
header('Content-Type: application/json');
$sessionId = (int) ($_GET['session_id'] ?? 0);
$pdo = db();
$owner = $pdo->prepare('SELECT user_id FROM toilet_sessions WHERE id=? LIMIT 1');
$owner->execute([$sessionId]);
$session = $owner->fetch();
if (!$session || (user()['role'] !== 'admin' && (int) $session['user_id'] !== (int) user()['id'])) {
	http_response_code(403);
	echo json_encode(['photos' => []]);
	exit;
}
$statement = $pdo->prepare("SELECT phase, file_path FROM session_photos WHERE session_id=? ORDER BY phase, id");
$statement->execute([$sessionId]);
$photos = $statement->fetchAll();
echo json_encode(['photos' => $photos]);
