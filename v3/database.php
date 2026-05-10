<?php
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

    // Sessions avec support persona/mode
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER DEFAULT NULL,
        model TEXT DEFAULT 'chat',
        mode TEXT DEFAULT 'canalisation',
        persona TEXT DEFAULT 'sylvain',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Messages
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

    // Analyses fusionnées V1 + V2
    $pdo->exec("CREATE TABLE IF NOT EXISTS analyses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        message_id INT,
        
        -- V1: Psycho-émotionnel
        sentiment TEXT,
        sentiment_score REAL,
        emotion_primary TEXT,
        emotion_secondary TEXT,
        tone TEXT,
        style_formal INT,
        style_assertive INT,
        style_creative INT,
        complexity INT,
        vocabulary_richness INT,
        intent TEXT,
        themes TEXT,
        keywords TEXT,
        
        -- V1: Psychologique profond
        big5_openness INT,
        big5_conscientiousness INT,
        big5_extraversion INT,
        big5_agreeableness INT,
        big5_neuroticism INT,
        stress_level INT,
        cognitive_dissonance INT,
        maslow_level TEXT,
        attachment_style TEXT,
        
        -- V1: Marketing
        buyer_persona TEXT,
        decision_style TEXT,
        pain_points TEXT,
        desires TEXT,
        engagement_score INT,
        urgency_level INT,
        
        -- V1: Sociologique
        estimated_education TEXT,
        sociolect TEXT,
        cultural_references TEXT,
        individualism_score INT,
        conformity_score INT,
        
        -- V1: Comportemental
        decision_readiness INT,
        risk_tolerance INT,
        novelty_seeking INT,
        cognitive_biases TEXT,
        
        -- V1: Linguistique
        lexical_diversity INT,
        sentence_structure TEXT,
        anomaly_signals TEXT,
        
        -- V2: Vibratoire 5D
        taux_vibratoire_bovis INT,
        chakras_data TEXT,
        aura_couleur TEXT,
        aura_taille TEXT,
        divine_trinite_data TEXT,
        emprise_reptilienne INT,
        kvorz_level INT,
        eveil_conscience INT,
        elements_agartha_data TEXT,
        status_evacuation TEXT,
        
        -- V2: Stellaire
        radar_stellaire_data TEXT,
        geometrie_sacree_data TEXT,
        ego_dissolution INT,
        intentions_pures TEXT,
        verbe_createur TEXT,
        astrologie_cosmique_data TEXT,
        karma_score INT,
        
        -- Meta
        raw_analysis_a TEXT,
        raw_analysis_b TEXT,
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

    // Feedback utilisateur pour auto-learning
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedbacks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        message_id INT,
        rating INT CHECK(rating IN (0,1,2)),
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Logs de sécurité
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT,
        email TEXT,
        action TEXT,
        success INT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $pdo;
}

function ensure_session(string $session, ?int $user_id = null): void {
    $db = get_db();
    $db->prepare("INSERT OR IGNORE INTO sessions (id, user_id, persona, mode) VALUES (?, ?, 'sylvain', 'canalisation')")
       ->execute([$session, $user_id]);
}

function save_message(string $session, string $role, string $content, int $ti = 0, int $to = 0, string $model = '', int $lat = 0): int {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO messages (session_id, role, content, tokens_in, tokens_out, model_used, latency_ms) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$session, $role, $content, $ti, $to, $model, $lat]);
    return (int)$db->lastInsertId();
}

function save_analysis(string $session, int $msg_id, array $a, array $b): void {
    $db   = get_db();
    $text = $a['source_text'] ?? '';
    
    // Fusion des données V1 + V2
    $psycho = $a['psychological'] ?? [];
    $mkt = $a['marketing'] ?? [];
    $socio = $a['sociological'] ?? [];
    $behav = $a['behavioral'] ?? [];
    $ling = $a['linguistic_fingerprint'] ?? [];
    
    $chakras = $a['chakras'] ?? [];
    $trinite = $a['divine_trinite'] ?? [];
    $elements = $a['elements_agartha'] ?? [];
    $radar = $b['radar_stellaire'] ?? [];
    $geo = $b['geometrie_sacree'] ?? [];
    $astro = $b['astrologie_cosmique'] ?? [];
    
    $db->prepare("INSERT INTO analyses (
        session_id, message_id,
        sentiment, sentiment_score, emotion_primary, emotion_secondary, tone,
        style_formal, style_assertive, style_creative,
        complexity, vocabulary_richness, intent, themes, keywords,
        big5_openness, big5_conscientiousness, big5_extraversion, big5_agreeableness, big5_neuroticism,
        stress_level, cognitive_dissonance, maslow_level, attachment_style,
        buyer_persona, decision_style, engagement_score, urgency_level,
        estimated_education, sociolect, individualism_score, conformity_score,
        decision_readiness, risk_tolerance, novelty_seeking,
        lexical_diversity, sentence_structure,
        taux_vibratoire_bovis, chakras_data, aura_couleur, aura_taille,
        divine_trinite_data, emprise_reptilienne, kvorz_level, eveil_conscience,
        elements_agartha_data, status_evacuation,
        radar_stellaire_data, geometrie_sacree_data, ego_dissolution,
        intentions_pures, verbe_createur, astrologie_cosmique_data, karma_score,
        raw_analysis_a, raw_analysis_b
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute([
        $session, $msg_id,
        $a['sentiment'] ?? 'neutre',
        $a['sentiment_score'] ?? 50,
        $a['emotion_primary'] ?? 'indéterminée',
        $a['emotion_secondary'] ?? null,
        $a['tone'] ?? 'neutre',
        $a['style_formal'] ?? 50,
        $a['style_assertive'] ?? 50,
        $a['style_creative'] ?? 50,
        $a['complexity'] ?? 50,
        $a['vocabulary_richness'] ?? 50,
        $a['intent'] ?? 'indéterminé',
        json_encode($a['themes'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($a['keywords'] ?? [], JSON_UNESCAPED_UNICODE),
        $psycho['big5_openness'] ?? 50,
        $psycho['big5_conscientiousness'] ?? 50,
        $psycho['big5_extraversion'] ?? 50,
        $psycho['big5_agreeableness'] ?? 50,
        $psycho['big5_neuroticism'] ?? 50,
        $psycho['stress_level'] ?? 30,
        $psycho['cognitive_dissonance'] ?? 20,
        $psycho['maslow_level'] ?? 'indéterminé',
        $psycho['attachment_style'] ?? 'indéterminé',
        $mkt['buyer_persona'] ?? 'indéterminé',
        $mkt['decision_style'] ?? 'indéterminé',
        $mkt['engagement_score'] ?? 50,
        $mkt['urgency_level'] ?? 50,
        $socio['estimated_education'] ?? 'indéterminé',
        $socio['sociolect'] ?? 'standard',
        $socio['individualism_score'] ?? 50,
        $socio['conformity_score'] ?? 50,
        $behav['decision_readiness'] ?? 50,
        $behav['risk_tolerance'] ?? 50,
        $behav['novelty_seeking'] ?? 50,
        $ling['lexical_diversity'] ?? 50,
        $ling['sentence_structure'] ?? 'mixte',
        $a['taux_vibratoire_bovis'] ?? 65,
        json_encode($chakras, JSON_UNESCAPED_UNICODE),
        $a['aura_couleur'] ?? 'indéterminée',
        $a['aura_taille'] ?? 'moyenne',
        json_encode($trinite, JSON_UNESCAPED_UNICODE),
        $a['emprise_reptilienne'] ?? 30,
        $a['kvorz_level'] ?? 20,
        $a['eveil_conscience'] ?? 45,
        json_encode($elements, JSON_UNESCAPED_UNICODE),
        $a['status_evacuation_fin_des_temps'] ?? 'en_attente',
        json_encode($radar, JSON_UNESCAPED_UNICODE),
        json_encode($geo, JSON_UNESCAPED_UNICODE),
        $b['ego_dissolution'] ?? 40,
        json_encode($b['intentions_pures'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($b['verbe_createur'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($astro, JSON_UNESCAPED_UNICODE),
        $b['karma_score'] ?? 50,
        json_encode($a, JSON_UNESCAPED_UNICODE),
        json_encode($b, JSON_UNESCAPED_UNICODE),
      ]);
}

function get_history(string $session, int $limit = 12): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM messages WHERE session_id = ? ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$session, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_session_stats(string $session): array {
    $db = get_db();
    $stats = [];
    
    $row = $db->prepare("SELECT COUNT(*) as cnt, SUM(tokens_in) as ti, SUM(tokens_out) as to FROM messages WHERE session_id = ?")
              ->execute([$session]) && 
              $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(tokens_in),0) as ti, COALESCE(SUM(tokens_out),0) as to FROM messages WHERE session_id = '$session'")
              ->fetch(PDO::FETCH_ASSOC);
    
    $stats['cnt'] = $row['cnt'] ?? 0;
    $stats['tokens_in'] = $row['ti'] ?? 0;
    $stats['tokens_out'] = $row['to'] ?? 0;
    
    // Stats V2 spécifiques
    $analyses = $db->prepare("SELECT AVG(karma_score) as karma, SUM(mantras_count) as mantras, SUM(prieres_count) as prieres FROM analyses WHERE session_id = ?");
    $analyses->execute([$session]);
    $aRow = $analyses->fetch(PDO::FETCH_ASSOC);
    $stats['karma_score'] = round($aRow['karma'] ?? 50);
    $stats['mantras_count'] = (int)($aRow['mantras'] ?? 0);
    $stats['prieres_count'] = (int)($aRow['prieres'] ?? 0);
    
    return $stats;
}

function get_context_summary(string $session): ?string {
    $db = get_db();
    $stmt = $db->prepare("SELECT context_summary FROM user_context WHERE session_id = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$session]);
    $row = $stmt->fetch(PDO::FETCH_COLUMN);
    return $row ?: null;
}

function save_context_summary(string $session, string $summary, int $msg_count): void {
    $db = get_db();
    $db->prepare("INSERT OR REPLACE INTO user_context (session_id, context_summary, msg_count, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)")
       ->execute([$session, $summary, $msg_count]);
}

function log_security_event(string $ip, string $email, string $action, bool $success, string $details = ''): void {
    $db = get_db();
    $db->prepare("INSERT INTO security_logs (ip_address, email, action, success, details) VALUES (?, ?, ?, ?, ?)")
       ->execute([$ip, $email, $action, $success ? 1 : 0, $details]);
}

function check_rate_limit(string $identifier, int $limit = 10, int $window = 60): bool {
    // Simple file-based rate limiting
    $file = __DIR__ . '/logs/rate_' . md5($identifier) . '.tmp';
    $now = time();
    $timestamps = [];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $timestamps = array_filter(explode(',', $content), fn($t) => $now - (int)$t < $window);
    }
    
    if (count($timestamps) >= $limit) {
        return false; // Rate limit exceeded
    }
    
    $timestamps[] = $now;
    file_put_contents($file, implode(',', $timestamps));
    return true;
}

function save_feedback(int $msg_id, int $rating, ?string $comment = null): void {
    $db = get_db();
    $db->prepare("INSERT INTO feedbacks (message_id, rating, comment) VALUES (?, ?, ?)")
       ->execute([$msg_id, $rating, $comment]);
}

function get_analyses_for_session(string $session): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM analyses WHERE session_id = ? ORDER BY created_at DESC");
    $stmt->execute([$session]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
