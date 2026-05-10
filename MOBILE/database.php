<?php
// ============================================================
// ALCYON MOBILE — DATABASE (identique v2)
// ============================================================

require_once 'config.php';

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        init_schema($pdo);
    }
    return $pdo;
}

function init_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sid TEXT UNIQUE NOT NULL,
        user_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        tokens_in INTEGER DEFAULT 0,
        tokens_out INTEGER DEFAULT 0,
        model_used TEXT,
        latency_ms INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS analyses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        message_id INTEGER,
        analysis_a TEXT,
        analysis_b TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS context_summaries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT UNIQUE NOT NULL,
        summary TEXT,
        msg_count INTEGER DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function ensure_session($sid, $user_id = null) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE sid = ?");
    $stmt->execute([$sid]);
    if ((int)$stmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO sessions (sid, user_id) VALUES (?, ?)");
        $stmt->execute([$sid, $user_id]);
    }
}

function get_or_create_user($email) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (email) VALUES (?)");
        $stmt->execute([$email]);
        $user_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}

function save_message($session, $role, $content, $tokens_in, $tokens_out, $model, $latency) {
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT INTO messages (session_id, role, content, tokens_in, tokens_out, model_used, latency_ms) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$session, $role, $content, $tokens_in, $tokens_out, $model, $latency]);
    return $pdo->lastInsertId();
}

function get_history($session, $limit = 20) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE session_id = ? ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$session, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function save_analysis($session, $msg_id, $analysis_a, $analysis_b) {
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT INTO analyses (session_id, message_id, analysis_a, analysis_b) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session, $msg_id, json_encode($analysis_a, JSON_UNESCAPED_UNICODE), json_encode($analysis_b, JSON_UNESCAPED_UNICODE)]);
}

function get_session_stats($session) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM messages WHERE session_id = ?");
    $stmt->execute([$session]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    return $stats ?: ['cnt' => 0];
}

function get_context_summary($session) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT summary FROM context_summaries WHERE session_id = ?");
    $stmt->execute([$session]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['summary'] ?? '';
}

function save_context_summary($session, $summary, $msg_count) {
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO context_summaries (session_id, summary, msg_count) VALUES (?, ?, ?)");
    $stmt->execute([$session, $summary, $msg_count]);
}
