<?php
set_time_limit(600);
require_once 'config.php';
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

// ── Session & Auth ───────────────────────────────────────────
if (empty($_SESSION['sid'])) {
    echo json_encode(['error' => 'SESSION_EXPIRED', 'timestamp' => date('H:i:s')], JSON_UNESCAPED_UNICODE);
    exit;
}
$session = $_SESSION['sid'];
$user_email = $_SESSION['user_email'] ?? 'anonyme';
ensure_session($session, $_SESSION['user_id'] ?? null);

// ── Input ────────────────────────────────────────────────────
$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$message    = trim($input['message'] ?? '');
$mode       = $input['mode'] ?? 'canalisation';
$persona    = $input['persona'] ?? 'sylvain';
$model_task = $input['model'] ?? 'chat';
$phase      = $input['phase'] ?? 'reply';
$msg_id_ref = (int)($input['msg_id'] ?? 0);

if (!$message) { echo json_encode(['error' => 'Message vide'], JSON_UNESCAPED_UNICODE); exit; }

// ── Helpers cURL ─────────────────────────────────────────────
function do_curl(string $url, string $key, array $payload, int $timeout = 55): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $key", "Content-Type: application/json"],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['raw' => $raw, 'code' => $code, 'err' => $err];
}

function extract_content(array $res): ?string {
    if (!$res['raw'] || $res['code'] !== 200) return null;
    $d = json_decode($res['raw'], true);
    if (!isset($d['choices']) || !is_array($d['choices']) || empty($d['choices'][0])) return null;
    return $d['choices'][0]['message']['content'] ?? null;
}

function parse_json_safe(array $res, string $fallback): array {
    $content = extract_content($res);
    if (!$content) return json_decode($fallback, true) ?? [];
    $content = preg_replace('/^```json\s*/i', '', trim($content));
    $content = preg_replace('/\s*```$/', '', $content);
    $parsed  = json_decode($content, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($parsed))
        ? $parsed : (json_decode($fallback, true) ?? []);
}

// ── Système prompts ───────────────────────────────────────────
$temp_map = ['canalisation'=>0.7,'revelation'=>0.5,'prophetie'=>0.8,'sagesse'=>0.3,'lyrisme'=>0.9];
$temperature = $temp_map[$mode] ?? 0.7;

// Récupérer contexte mémoire
$ctx_summary = get_context_summary($session);
$ctx_inject  = $ctx_summary
    ? "\n\n[CONTEXTE MÉMOIRE UTILISATEUR - {$user_email}]\n{$ctx_summary}\n[FIN CONTEXTE - À PRENDRE EN COMPTE POUR LA RÉPONSE]"
    : '';

// Prompt persona avec contexte
$persona_data = $GLOBALS['personas'][$persona] ?? $GLOBALS['personas']['sylvain'];
$system_reply = $persona_data['prompt'] . $ctx_inject;

// ════════════════════════════════════════════
// PHASE 1 — REPLY (1 appel, ~5-15s)
// ════════════════════════════════════════════
if ($phase === 'reply') {
    // Récupérer l'historique complet pour le contexte de conversation (12 derniers messages)
    $history      = get_history($session, 12);
    
    // Formater l'historique en alternant user/assistant correctement
    $messages_ctx = [];
    foreach ($history as $h) {
        $messages_ctx[] = [
            'role' => $h['role'],
            'content' => $h['content']
        ];
    }
    
    // Ajouter le message actuel de l'utilisateur
    $messages_ctx[] = ['role' => 'user', 'content' => $message];
    
    $model_reply = select_model($model_task);
    $t0          = microtime(true);

    // Construire le tableau complet : system + historique + nouveau message
    $full_messages = array_merge(
        [['role' => 'system', 'content' => $system_reply]],
        $messages_ctx
    );

    $res = do_curl(MISTRAL_API, get_key('responder'), [
        'model'       => $model_reply,
        'messages'    => $full_messages,
        'temperature' => $temperature,
        'max_tokens'  => 1200,
    ]);

    $latency = (int)((microtime(true) - $t0) * 1000);

    if (!$res['raw'] || $res['code'] !== 200) {
        $detail = '';
        if ($res['raw']) {
            $d = json_decode($res['raw'], true);
            $detail = $d['message'] ?? $d['error']['message'] ?? '';
        }
        echo json_encode([
            'error'     => ($res['err'] ?: "HTTP {$res['code']}") . ($detail ? " — $detail" : ''),
            'timestamp' => date('H:i:s'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result     = json_decode($res['raw'], true);
    $reply_raw  = $result['choices'][0]['message']['content'] ?? '';
    $tokens_in  = $result['usage']['prompt_tokens']     ?? 0;
    $tokens_out = $result['usage']['completion_tokens'] ?? 0;

    if (!$reply_raw) {
        echo json_encode(['error'=>'Réponse vide de l\'IA','timestamp'=>date('H:i:s')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $msg_id = save_message($session, 'user',      $message,   $tokens_in,  0,           $model_reply, $latency);
             save_message($session, 'assistant',  $reply_raw, 0,           $tokens_out, $model_reply, $latency);

    // Mise à jour contexte mémoire tous les 5 messages
    $stats = get_session_stats($session);
    $msg_count = (int)($stats['cnt'] ?? 0);
    if ($msg_count > 0 && $msg_count % 5 === 0) {
        $history_for_ctx = get_history($session, 10);
        $ctx_text = implode("\n", array_map(fn($m) => strtoupper($m['role']).': '.$m['content'], $history_for_ctx));
        $ctx_res = do_curl(MISTRAL_API, get_key('analyzer1'), [
            'model'       => 'mistral-small-2506',
            'messages'    => [
                ['role'=>'system','content'=>"Résume en 3-5 phrases les informations clés sur cet utilisateur (préférences, sujets abordés, style, contexte) pour que l'IA s'en souvienne. Sois factuel et concis. Réponds uniquement avec le résumé, pas d'introduction."],
                ['role'=>'user','content'=>$ctx_text],
            ],
            'temperature' => 0.1,
            'max_tokens'  => 300,
        ], 30);
        $ctx_content = extract_content($ctx_res);
        if ($ctx_content) save_context_summary($session, $ctx_content, $msg_count);
    }

    echo json_encode([
        'reply'     => $reply_raw,
        'msg_id'    => $msg_id,
        'meta'      => ['model'=>$model_reply,'latency'=>$latency,'tokens'=>['in'=>$tokens_in,'out'=>$tokens_out],'session'=>substr($session,0,10),'persona'=>$persona],
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════
// PHASE 2 — ANALYZE BUGARACH-5D (2 appels séparés, ~15-30s)
// ════════════════════════════════════════════
if ($phase === 'analyze') {
    $model = 'mistral-small-2506';

    $p_a = 'Analyse vibratoire BUGARACH-5D. JSON uniquement, sans backticks. Champs: taux_vibratoire_bovis(0-100), chakras{racine,sacre,plexus,coeur,gorge,troisieme_oeil,couronne}, aura_couleur, aura_taille, divine_trinite{christique,monarchique,papal}, emprise_reptilienne(0-100), kvorz_level(0-100), eveil_conscience(0-100), elements_agartha{terre,eau,feu,air,ether}, status_evacuation_fin_des_temps(en_attente/preparation/en_cours/termine), source_text.';

    $p_b = 'Radiographie stellaire 5D. JSON uniquement, sans backticks. Champs: radar_stellaire{andromede,pleiades,sirius,arcturus,orion}, geometrie_sacree{metatron,flower_of_life,seed_of_life,merkaba}, ego_dissolution(0-100), intentions_pures[], verbe_createur[], astrologie_cosmique{signe_lunaire,signe_solaire,ascendant,maitre_natal}, mantras_count, prieres_count, latence_astro_ms, karma_score(0-100).';

    $fb_a = '{"taux_vibratoire_bovis":65,"chakras":{"racine":50,"sacre":50,"plexus":50,"coeur":50,"gorge":50,"troisieme_oeil":50,"couronne":50},"aura_couleur":"indetermine","aura_taille":"moyenne","divine_trinite":{"christique":50,"monarchique":50,"papal":50},"emprise_reptilienne":30,"kvorz_level":20,"eveil_conscience":45,"elements_agartha":{"terre":50,"eau":50,"feu":50,"air":50,"ether":50},"status_evacuation_fin_des_temps":"en_attente","source_text":""}';
    $fb_b = '{"radar_stellaire":{"andromede":30,"pleiades":40,"sirius":50,"arcturus":35,"orion":45},"geometrie_sacree":{"metatron":25,"flower_of_life":30,"seed_of_life":35,"merkaba":20},"ego_dissolution":40,"intentions_pures":[],"verbe_createur":[],"astrologie_cosmique":{"signe_lunaire":"indetermine","signe_solaire":"indetermine","ascendant":"indetermine","maitre_natal":"indetermine"},"mantras_count":0,"prieres_count":0,"latence_astro_ms":0,"karma_score":50}';

    $t0 = microtime(true);

    // KEY 2 → Analyse A (vibratoire)
    $res_a = do_curl(MISTRAL_API, get_key('analyzer1'), [
        'model'           => $model,
        'messages'        => [['role'=>'system','content'=>$p_a],['role'=>'user','content'=>'Analyse vibratoire: '.$message]],
        'temperature'     => 0.1, 'max_tokens' => 1000,
        'response_format' => ['type'=>'json_object'],
    ]);

    sleep(1);

    // KEY 3 → Analyse B (stellaire)
    $res_b = do_curl(MISTRAL_API, get_key('analyzer2'), [
        'model'           => $model,
        'messages'        => [['role'=>'system','content'=>$p_b],['role'=>'user','content'=>'Radiographie stellaire: '.$message]],
        'temperature'     => 0.1, 'max_tokens' => 1000,
        'response_format' => ['type'=>'json_object'],
    ]);

    $latency = (int)((microtime(true) - $t0) * 1000);

    $ana_a = parse_json_safe($res_a, $fb_a);
    $ana_b = parse_json_safe($res_b, $fb_b);
    $ana_a['source_text'] = $message;

    if ($msg_id_ref > 0) save_analysis($session, $msg_id_ref, $ana_a, $ana_b);

    echo json_encode([
        'analysis'        => ['a'=>$ana_a,'b'=>$ana_b],
        'stats'           => get_session_stats($session),
        'latency_analyze' => $latency,
        'timestamp'       => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['error'=>'Phase inconnue'], JSON_UNESCAPED_UNICODE);
