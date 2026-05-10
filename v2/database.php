<?php
/**
 * ALCYON v4.0 — PORTAIL DE BUGARACH
 * Base de données SQLite
 */
require_once 'config.php';

function get_db(): PDO {
    if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0755, true);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode=WAL");

    // Users (login par email)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER DEFAULT NULL,
        persona TEXT DEFAULT 'durif',
        mode TEXT DEFAULT 'canalisation',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        role TEXT,
        content TEXT,
        tokens_in INT DEFAULT 0,
        tokens_out INT DEFAULT 0,
        model_used TEXT,
        latency_ms INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS analyses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        message_id INT,
        taux_bovis INT,
        taux_label TEXT,
        chakra_dominant TEXT,
        aura_couleur TEXT,
        christ_cosmique INT,
        monarque_sacre INT,
        pape_spirituel INT,
        emprise_reptilienne INT,
        kvorz INT,
        niveau_eveil INT,
        elements_terre INT,
        elements_eau INT,
        elements_feu INT,
        elements_air INT,
        elements_ether INT,
        evacuation_eligible INT,
        evacuation_percent INT,
        evacuation_note TEXT,
        geo_forme TEXT,
        geo_nombre TEXT,
        geo_cristal TEXT,
        geo_portail TEXT,
        ego INT,
        humilite INT,
        fierte INT,
        intentions TEXT,
        themes TEXT,
        keywords TEXT,
        verbe_parole TEXT,
        verbe_action TEXT,
        verbe_creation TEXT,
        astro_ascendant TEXT,
        astro_lunaire TEXT,
        astro_solaire TEXT,
        raw_analysis TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Contexte mémoire par utilisateur (résumé glissant)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_context (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        context_summary TEXT,
        msg_count INT DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $pdo;
}

function ensure_session(string $session, ?int $user_id = null): void {
    $db = get_db();
    $db->prepare("INSERT OR IGNORE INTO sessions (id, user_id) VALUES (?,?)")->execute([$session, $user_id]);
}

function save_message(string $session, string $role, string $content, int $ti = 0, int $to = 0, string $model = '', int $lat = 0): int {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO messages (session_id,role,content,tokens_in,tokens_out,model_used,latency_ms) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$session, $role, $content, $ti, $to, $model, $lat]);
    return (int)$db->lastInsertId();
}

function save_analysis(string $session, int $msg_id, array $a): void {
    $db = get_db();
    $elems = $a['elements'] ?? [];
    $db->prepare("INSERT INTO analyses (
        session_id,message_id,
        taux_bovis,taux_label,chakra_dominant,aura_couleur,
        christ_cosmique,monarque_sacre,pape_spirituel,
        emprise_reptilienne,kvorz,niveau_eveil,
        elements_terre,elements_eau,elements_feu,elements_air,elements_ether,
        evacuation_eligible,evacuation_percent,evacuation_note,
        geo_forme,geo_nombre,geo_cristal,geo_portail,
        ego,humilite,fierte,
        intentions,themes,keywords,
        verbe_parole,verbe_action,verbe_creation,
        astro_ascendant,astro_lunaire,astro_solaire,
        raw_analysis
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
        $session, $msg_id,
        $a['taux_bovis'] ?? 50, $a['taux_label'] ?? 'NORMAL', $a['chakra_dominant'] ?? '—', $a['aura_couleur'] ?? '—',
        $a['christ_cosmique'] ?? 50, $a['monarque_sacre'] ?? 50, $a['pape_spirituel'] ?? 50,
        $a['emprise_reptilienne'] ?? 30, $a['kvorz'] ?? 20, $a['niveau_eveil'] ?? 50,
        $elems['terre'] ?? 50, $elems['eau'] ?? 50, $elems['feu'] ?? 50, $elems['air'] ?? 50, $elems['ether'] ?? 50,
        $a['evacuation_eligible'] ? 1 : 0, $a['evacuation_percent'] ?? 0, $a['evacuation_note'] ?? '—',
        $a['geo_forme'] ?? '—', $a['geo_nombre'] ?? '—', $a['geo_cristal'] ?? '—', $a['geo_portail'] ?? '—',
        $a['ego'] ?? 50, $a['humilite'] ?? 50, $a['fierte'] ?? 50,
        $a['intentions'] ?? 'INDÉTERMINÉ', json_encode($a['themes'] ?? []), json_encode($a['keywords'] ?? []),
        $a['verbe_parole'] ?? '—', $a['verbe_action'] ?? '—', $a['verbe_creation'] ?? '—',
        $a['astro_ascendant'] ?? '—', $a['astro_lunaire'] ?? '—', $a['astro_solaire'] ?? '—',
        json_encode($a),
    ]);
}

function get_history(string $session, int $limit = 20): array {
    $db   = get_db();
    $stmt = $db->prepare("SELECT role,content FROM messages WHERE session_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$session, $limit]);
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function get_context_summary(string $session): string {
    $db   = get_db();
    $stmt = $db->prepare("SELECT context_summary FROM user_context WHERE session_id=? ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$session]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['context_summary'] ?? '';
}

function save_context_summary(string $session, string $summary, int $msg_count): void {
    $db = get_db();
    $existing = $db->prepare("SELECT id FROM user_context WHERE session_id=?");
    $existing->execute([$session]);
    if ($existing->fetch()) {
        $db->prepare("UPDATE user_context SET context_summary=?, msg_count=?, updated_at=CURRENT_TIMESTAMP WHERE session_id=?")->execute([$summary, $msg_count, $session]);
    } else {
        $db->prepare("INSERT INTO user_context (session_id,context_summary,msg_count) VALUES (?,?,?)")->execute([$session, $summary, $msg_count]);
    }
}

function get_session_stats(string $session): array {
    $db = get_db();
    $m  = $db->prepare("SELECT COUNT(*) as cnt, SUM(tokens_in+tokens_out) as tok FROM messages WHERE session_id=?");
    $m->execute([$session]);
    $ms = $m->fetch(PDO::FETCH_ASSOC);
    return $ms ?? [];
}
