<?php
require_once 'config.php';
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['sid'])) {
    echo json_encode(['error' => 'Session expirée'], JSON_UNESCAPED_UNICODE);
    exit;
}

$session = $_SESSION['sid'];

// Supprimer messages et analyses de la session
$pdo = get_db();
$stmt = $pdo->prepare("DELETE FROM messages WHERE session_id = ?");
$stmt->execute([$session]);
$stmt = $pdo->prepare("DELETE FROM analyses WHERE session_id = ?");
$stmt->execute([$session]);
$stmt = $pdo->prepare("DELETE FROM context_summaries WHERE session_id = ?");
$stmt->execute([$session]);

echo json_encode(['success' => true, 'message' => 'Session purifiée'], JSON_UNESCAPED_UNICODE);
