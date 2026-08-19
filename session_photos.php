<?php
session_start(); require_once __DIR__ . '/config.php'; require_admin();
header('Content-Type: application/json');
$sessionId = (int) ($_GET['session_id'] ?? 0);
$pdo = db();
$statement = $pdo->prepare("SELECT phase, file_path FROM session_photos WHERE session_id=? ORDER BY phase, id");
$statement->execute([$sessionId]);
$photos = $statement->fetchAll();
echo json_encode(['photos' => $photos]);
