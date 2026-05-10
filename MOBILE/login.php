<?php
require_once 'config.php';
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Email invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = get_or_create_user($email);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $email;

if (empty($_SESSION['sid'])) {
    $_SESSION['sid'] = bin2hex(random_bytes(16));
}

ensure_session($_SESSION['sid'], $user['id']);

echo json_encode([
    'success' => true,
    'email' => $email,
    'sid' => $_SESSION['sid'],
    'member_since' => $user['created_at'],
], JSON_UNESCAPED_UNICODE);
