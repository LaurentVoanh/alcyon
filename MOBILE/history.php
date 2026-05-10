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
$messages = get_history($session, 50);

echo json_encode(['messages' => $messages], JSON_UNESCAPED_UNICODE);
