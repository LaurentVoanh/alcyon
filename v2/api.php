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
$mode       = $input['mode']    ?? 'canalisation';
$persona    = $input['persona'] ?? 'durif';
$phase      = $input['phase']   ?? 'reply';
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
$global_persona = get_persona_prompt($persona);
$global_mode = $MODES[$mode] ?? $MODES['canalisation'];
$temperature = $global_mode['temp'] ?? 0.7;
$mode_add = $global_mode['prompt_add'] ?? '';

$system_reply = $global_persona . ($mode_add ? ' ' . $mode_add : '');

// Récupérer contexte mémoire
$ctx_summary = get_context_summary($session);
$ctx_inject  = $ctx_summary
    ? "\n\n[MÉMOIRE CONTEXTE UTILISATEUR ({$user_email})]\n$ctx_summary\n[FIN MÉMOIRE]"
    : '';

$system_reply .= $ctx_inject;

// ════════════════════════════════════════════
// PHASE 1 — REPLY (1 appel, ~5-15s)
// ════════════════════════════════════════════
if ($phase === 'reply') {
    $history      = get_history($session, 8);
    $messages_ctx = array_map(fn($m) => ['role'=>$m['role'],'content'=>$m['content']], $history);
    $messages_ctx[] = ['role'=>'user','content'=>$message];

    $model_reply = MODEL_REPLY;
    $t0          = microtime(true);

    $res = do_curl(MISTRAL_API, MISTRAL_KEY, [
        'model'       => $model_reply,
        'messages'    => array_merge([['role'=>'system','content'=>$system_reply]], $messages_ctx),
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
        $ctx_res = do_curl(MISTRAL_API, MISTRAL_KEY, [
            'model'       => MODEL_ANALYZE,
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
        'meta'      => ['model'=>$model_reply,'latency'=>$latency,'tokens'=>['in'=>$tokens_in,'out'=>$tokens_out],'session'=>substr($session,0,10),'persona'=>get_persona_name($persona)],
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════
// PHASE 2 — ANALYZE VIBRATOIRE (1 appel JSON)
// ════════════════════════════════════════════
if ($phase === 'analyze') {
    $model = MODEL_ANALYZE;

    $analyze_prompt = `Analyse vibratoire mystique complète. Réponds UNIQUEMENT avec un JSON valide, sans backticks, sans introduction.
  
Champs requis :
{
  "taux_bovis": number (0-100),
  "taux_label": string ("BAS"|"NORMAL"|"ÉLEVÉ"|"TRÈS ÉLEVÉ"),
  "chakra_dominant": string ("RACINE"|"SACRÉ"|"PLEXUS"|"CŒUR"|"GORGE"|"3ᵉ ŒIL"|"COURONNE"),
  "aura_couleur": string,
  "christ_cosmique": number (0-100),
  "monarque_sacre": number (0-100),
  "pape_spirituel": number (0-100),
  "emprise_reptilienne": number (0-100),
  "kvorz": number (0-100),
  "niveau_eveil": number (0-100),
  "elements": {"terre": number, "eau": number, "feu": number, "air": number, "ether": number},
  "evacuation_eligible": boolean,
  "evacuation_percent": number (0-100),
  "evacuation_note": string,
  "radar_stellaire": [number, number, number, number, number, number],
  "geo_forme": string,
  "geo_nombre": string,
  "geo_cristal": string,
  "geo_portail": string,
  "ego": number (0-100),
  "humilite": number (0-100),
  "fierte": number (0-100),
  "intentions": string,
  "themes": string[],
  "keywords": string[],
  "verbe_parole": string,
  "verbe_action": string,
  "verbe_creation": string,
  "astro_ascendant": string,
  "astro_lunaire": string,
  "astro_solaire": string
}

Message à analyser : "${message}"`;

    $fb_default = '{"taux_bovis":50,"taux_label":"NORMAL","chakra_dominant":"CŒUR","aura_couleur":"Indéterminée","christ_cosmique":50,"monarque_sacre":50,"pape_spirituel":50,"emprise_reptilienne":30,"kvorz":20,"niveau_eveil":50,"elements":{"terre":50,"eau":50,"feu":50,"air":50,"ether":50},"evacuation_eligible":false,"evacuation_percent":0,"evacuation_note":"—","radar_stellaire":[50,50,50,50,50,50],"geo_forme":"—","geo_nombre":"—","geo_cristal":"—","geo_portail":"—","ego":50,"humilite":50,"fierte":50,"intentions":"INDÉTERMINÉ","themes":[],"keywords":[],"verbe_parole":"—","verbe_action":"—","verbe_creation":"—","astro_ascendant":"—","astro_lunaire":"—","astro_solaire":"—"}';

    $t0 = microtime(true);

    $res = do_curl(MISTRAL_API, MISTRAL_KEY, [
        'model'           => $model,
        'messages'        => [['role'=>'system','content'=>'Tu es un analyseur vibratoire mystique. Tu réponds uniquement avec du JSON valide.'],['role'=>'user','content'=>$analyze_prompt]],
        'temperature'     => 0.1,
        'max_tokens'      => 1000,
        'response_format' => ['type'=>'json_object'],
    ]);

    $latency = (int)((microtime(true) - $t0) * 1000);

    $analysis = parse_json_safe($res, $fb_default);

    if ($msg_id_ref > 0) save_analysis($session, $msg_id_ref, $analysis);

    echo json_encode([
        'analysis'        => $analysis,
        'stats'           => get_session_stats($session),
        'latency_analyze' => $latency,
        'timestamp'       => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['error'=>'Phase inconnue'], JSON_UNESCAPED_UNICODE);
