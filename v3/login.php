<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate limiting check
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($client_ip . '_' . $email, 5, 300)) { // 5 logins / 5min
    log_security_event($client_ip, $email, 'LOGIN_RATE_LIMIT', false, 'Too many login attempts');
    http_response_code(429);
    echo json_encode(['error' => 'Trop de tentatives — attendez 5 minutes'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = get_db();
    
    // Chercher ou créer l'utilisateur
    $stmt = $db->prepare("SELECT id, created_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Créer nouvel utilisateur
        $stmt = $db->prepare("INSERT INTO users (email) VALUES (?)");
        $stmt->execute([$email]);
        $user_id = (int)$db->lastInsertId();
        $created_at = date('Y-m-d H:i:s');
        log_security_event($client_ip, $email, 'USER_CREATED', true, 'New user registered');
    } else {
        $user_id = $user['id'];
        $created_at = $user['created_at'];
        log_security_event($client_ip, $email, 'USER_LOGIN', true, 'Existing user logged in');
    }
    
    // Créer nouvelle session
    $sid = bin2hex(random_bytes(16));
    $stmt = $db->prepare("INSERT INTO sessions (id, user_id, persona, mode) VALUES (?, ?, 'sylvain', 'canalisation')");
    $stmt->execute([$sid, $user_id]);
    
    // Stocker en session PHP
    $_SESSION['sid'] = $sid;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    
    // Générer token CSRF
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
    
    echo json_encode([
        'success' => true,
        'email' => $email,
        'member_since' => $created_at,
        'sid' => $sid,
        'csrf_token' => $csrf_token,
        'timestamp' => date('H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    log_error('DATABASE_ERROR', $e->getMessage(), ['email' => $email]);
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données'], JSON_UNESCAPED_UNICODE);
}
