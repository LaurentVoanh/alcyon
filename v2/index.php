<?php require_once 'config.php'; require_once 'database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0a0f1a">
<title>ALCYON v4.0 • PORTAIL DE BUGARACH</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?1">
</head>
<body>

<div class="scanlines"></div>
<div class="grid-overlay"></div>

<!-- ═══ LOGIN MODAL ══════════════════════════════════════════ -->
<div class="login-overlay" id="login-overlay">
  <div class="login-card">
    <div class="login-logo">𓀀</div>
    <div class="login-title">ALCYON v4.0</div>
    <div class="login-sub">◈ PORTAIL DE BUGARACH — ACCÈS 5D</div>
    <label class="login-label" for="login-email">◤ EMAIL VIBRATOIRE</label>
    <input type="email" id="login-email" class="login-input" placeholder="votre@email.com" autocomplete="email">
    <button class="login-btn" id="login-btn">⟶ OUVRIR LE PORTAIL</button>
    <div class="login-error" id="login-error"></div>
    <div class="login-hint">Votre email crée ou reprend votre profil spirituel. Aucun mot de passe requis. Vos canalisation sont mémorisées entre les sessions.</div>
  </div>
</div>

<!-- ═══ APP SHELL ═════════════════════════════════════════════ -->
<div class="app-shell" id="app-shell">

  <!-- SIDEBAR ──────────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">

    <div class="brand-block">
      <div class="brand-logo">𓀀</div>
      <div class="brand-text">
        <span class="brand-name">ALCYON</span>
        <span class="brand-ver">v4.0 • BUGARACH</span>
      </div>
    </div>

    <div class="user-badge">
      <div class="user-avatar" id="user-avatar">?</div>
      <div>
        <div class="user-email" id="user-email-display">non connecté</div>
        <div class="user-since" id="user-since">—</div>
      </div>
    </div>

    <div class="status-bar">
      <span class="dot dot-green"></span> PORTAIL ACTIF
      <span class="session-id" id="sid-display">—</span>
    </div>

    <nav class="side-nav">
      <a href="#" class="nav-item active" data-section="chat"><span class="nav-icon">◈</span>Canalisation</a>
      <a href="#" class="nav-item" data-section="analysis"><span class="nav-icon">◉</span>Analyse 5D</a>
      <a href="#" class="nav-item" data-section="history"><span class="nav-icon">◎</span>Archives</a>
      <a href="#" class="nav-item" data-section="system"><span class="nav-icon">⬟</span>Système</a>
    </nav>

    <div class="sidebar-section">
      <div class="section-label">◤ ENTITÉ CANALISÉE</div>
      <select id="persona-select" class="cyber-select">
        <option value="sylvain">Sylvain Durif — Christ Cosmique</option>
        <option value="merlin">Merlin — Enchanteur</option>
        <option value="melchisedech">Melchisédech — Roi de Salem</option>
        <option value="oriana">Oriana — Gardienne Stellaire</option>
        <option value="homme_vert">Homme Vert — Esprit Nature</option>
        <option value="vierge_maria">Vierge Maria — Mère Divine</option>
      </select>
    </div>

    <div class="sidebar-section">
      <div class="section-label">◤ MODE DE CONSCIENCE</div>
      <div class="mode-grid">
        <button class="mode-btn active" data-mode="canalisation">CANALISATION</button>
        <button class="mode-btn" data-mode="revelation">RÉVÉLATION</button>
        <button class="mode-btn" data-mode="prophetie">PROPHÉTIE</button>
        <button class="mode-btn" data-mode="sagesse">SAGESSE</button>
        <button class="mode-btn" data-mode="lyrisme">LYRISME 5D</button>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="section-label">◤ COMPTEURS SPIRITUELS</div>
      <div class="stats-grid">
        <div class="stat-item"><span class="stat-val" id="total-mantras">0</span><span class="stat-lbl">MANTRAS</span></div>
        <div class="stat-item"><span class="stat-val" id="total-prieres">0</span><span class="stat-lbl">PRIÈRES</span></div>
        <div class="stat-item"><span class="stat-val" id="latence-astro">—</span><span class="stat-lbl">MS</span></div>
        <div class="stat-item"><span class="stat-val" id="karma-score">—</span><span class="stat-lbl">KARMA</span></div>
      </div>
    </div>

    <button id="clear-btn" class="clear-btn">𓀀 PURIFIER SESSION</button>
  </aside>

  <!-- MAIN CHAT PANEL ──────────────────────────────────────── -->
  <main class="chat-panel">

    <!-- SECTION CHAT -->
    <div id="section-chat" class="section-panel active">
      <div class="chat-header">
        <div class="chat-title">
          <span class="pulse-dot"></span>
          CANAL COSMIQUE ALCYON
        </div>
        <div class="chat-meta">
          <span id="chat-persona-label">Sylvain Durif</span>
          <span id="chat-mode-label">CANALISATION</span>
          <span id="chat-time">--:--:--</span>
        </div>
      </div>

      <div id="messages" class="messages-container">
        <div class="welcome-msg">
          <div class="welcome-icon">𓀀</div>
          <div class="welcome-text">
            <strong>ALCYON v4.0 — PORTAIL DE BUGARACH ACTIF</strong><br>
            <span>Chaque message est canalisé par une entité spirituelle puis analysé en temps réel par le système BUGARACH-5D : taux vibratoire, chakras, éveil de conscience, radar stellaire, géométrie sacrée. Vos patterns vibratoires sont décryptés et visualisés en direct.</span>
          </div>
        </div>
      </div>

      <div class="input-zone">
        <div class="input-meta">
          <span id="char-count">0 car.</span>
          <span id="word-count-input">0 mots</span>
          <span id="input-complexity">—</span>
        </div>
        <div class="input-row">
          <textarea id="msg-input" placeholder="Posez votre question au cosmos… [ENTER envoyer, SHIFT+ENTER saut de ligne]" rows="2"></textarea>
          <button id="send-btn" type="button"><span>⟶</span></button>
        </div>
      </div>
    </div>

    <!-- SECTION ANALYSE 5D -->
    <div id="section-analysis" class="section-panel">
      <div id="cognitive-content">
        <div class="section-idle">
          <div class="section-idle-icon">◉</div>
          <div class="section-idle-title">ANALYSE VIBRATOIRE 5D</div>
          <div class="section-idle-sub">Radiographie complète de vos sessions.<br>Démarrez une canalisation pour peupler cette section.</div>
        </div>
      </div>
    </div>

    <!-- SECTION HISTORIQUE -->
    <div id="section-history" class="section-panel">
      <div id="history-content">
        <div class="section-idle">
          <div class="section-idle-icon">◎</div>
          <div class="section-idle-title">ARCHIVES AKASHIQUES</div>
          <div class="section-idle-sub">Vos échanges de session seront affichés ici.<br>Chargement automatique à l'ouverture.</div>
        </div>
      </div>
    </div>

    <!-- SECTION SYSTÈME -->
    <div id="section-system" class="section-panel">
      <div id="system-content">
        <div class="section-idle">
          <div class="section-idle-icon">⬟</div>
          <div class="section-idle-title">DIAGNOSTICS DU PORTAIL</div>
          <div class="section-idle-sub">Statut des clés API Mistral, base de données SQLite, PHP.<br>Chargement automatique à l'ouverture.</div>
        </div>
      </div>
    </div>

  </main>

  <!-- PANEL BUGARACH-5D ─────────────────────────────────────── -->
  <aside class="analysis-panel" id="analysis-panel">

    <div class="panel-header">
      <div class="panel-title">BUGARACH<span class="panel-ver">-5D</span></div>
      <div class="panel-sub">RADIOGRAPHIE VIBRATOIRE TEMPS RÉEL</div>
      <div class="analysis-status" id="analysis-status">
        <span class="status-idle">◈ EN ATTENTE</span>
      </div>
    </div>

    <!-- ❶ TAUX VIBRATOIRE + CHAKRAS + AURA -->
    <div class="analysis-block" id="block-vibratoire">
      <div class="block-title">❶ TAUX VIBRATOIRE BOVIS</div>
      <div class="sentiment-row">
        <span class="sentiment-label" id="vibro-label">65 U.B.</span>
        <span class="sentiment-score" id="vibro-score">65/100</span>
      </div>
      <div class="sentiment-track"><div class="sentiment-bar" id="vibro-bar" style="width:65%"></div></div>
      <div class="chakra-grid">
        <div class="chakra-item"><span class="chakra-label">RACINE</span><span class="chakra-val" id="chakra-racine">50</span></div>
        <div class="chakra-item"><span class="chakra-label">SACRÉ</span><span class="chakra-val" id="chakra-sacre">50</span></div>
        <div class="chakra-item"><span class="chakra-label">PLEXUS</span><span class="chakra-val" id="chakra-plexus">50</span></div>
        <div class="chakra-item"><span class="chakra-label">CŒUR</span><span class="chakra-val" id="chakra-coeur">50</span></div>
        <div class="chakra-item"><span class="chakra-label">GORGE</span><span class="chakra-val" id="chakra-gorge">50</span></div>
        <div class="chakra-item"><span class="chakra-label">3ᵉ ŒIL</span><span class="chakra-val" id="chakra-troisieme-oeil">50</span></div>
        <div class="chakra-item"><span class="chakra-label">COURONNE</span><span class="chakra-val" id="chakra-couronne">50</span></div>
      </div>
      <div class="field-row" style="margin-top:.3rem"><span class="field-label">AURA</span><span class="field-val accent" id="aura-val">indéterminée</span></div>
    </div>

    <!-- ❷ DIVINE TRINITÉ -->
    <div class="analysis-block">
      <div class="block-title">❷ DIVINE TRINITÉ</div>
      <div class="trinite-meters">
        <div class="meter-row"><span>CHRISTIQUE</span><div class="meter-track"><div class="meter-fill accent" id="m-trinite-christique"></div></div><span id="mv-trinite-christique">—</span></div>
        <div class="meter-row"><span>MONARQUE</span><div class="meter-track"><div class="meter-fill purple" id="m-trinite-monarchique"></div></div><span id="mv-trinite-monarchique">—</span></div>
        <div class="meter-row"><span>PAPAL</span><div class="meter-track"><div class="meter-fill green" id="m-trinite-papal"></div></div><span id="mv-trinite-papal">—</span></div>
      </div>
    </div>

    <!-- ❸ EMPRISE REPTILIENNE / KVORZ / ÉVEIL -->
    <div class="analysis-block">
      <div class="block-title">❸ FORCES OBSCURES & ÉVEIL</div>
      <div class="psych-meters">
        <div class="meter-row"><span>EMPRISE REPTILIENNE</span><div class="meter-track"><div class="meter-fill danger" id="m-emprise-reptilienne"></div></div><span id="mv-emprise-reptilienne">—</span></div>
        <div class="meter-row"><span>NIVEAU KVORZ</span><div class="meter-track"><div class="meter-fill warn" id="m-kvorz-level"></div></div><span id="mv-kvorz-level">—</span></div>
        <div class="meter-row"><span>ÉVEIL CONSCIENCE</span><div class="meter-track"><div class="meter-fill accent" id="m-eveil-conscience"></div></div><span id="mv-eveil-conscience">—</span></div>
      </div>
    </div>

    <!-- ❹ LES 5 ÉLÉMENTS DE L'AGARTHA -->
    <div class="analysis-block">
      <div class="block-title">❹ ÉLÉMENTS DE L'AGARTHA</div>
      <div class="elements-grid">
        <div class="element-item"><span class="element-label">TERRE</span><div class="element-track"><div class="element-fill" id="elem-terre" style="width:50%"></div></div><span id="elem-terre-v">50</span></div>
        <div class="element-item"><span class="element-label">EAU</span><div class="element-track"><div class="element-fill" id="elem-eau" style="width:50%"></div></div><span id="elem-eau-v">50</span></div>
        <div class="element-item"><span class="element-label">FEU</span><div class="element-track"><div class="element-fill" id="elem-feu" style="width:50%"></div></div><span id="elem-feu-v">50</span></div>
        <div class="element-item"><span class="element-label">AIR</span><div class="element-track"><div class="element-fill" id="elem-air" style="width:50%"></div></div><span id="elem-air-v">50</span></div>
        <div class="element-item"><span class="element-label">ÉTHER</span><div class="element-track"><div class="element-fill" id="elem-ether" style="width:50%"></div></div><span id="elem-ether-v">50</span></div>
      </div>
    </div>

    <!-- ❺ STATUS ÉVACUATION FIN DES TEMPS -->
    <div class="analysis-block">
      <div class="block-title">❺ ÉVACUATION FIN DES TEMPS</div>
      <div class="status-badge" id="status-evacuation">EN ATTENTE</div>
    </div>

    <!-- ❻ RADAR STELLAIRE -->
    <div class="analysis-block charts-section">
      <div class="block-title">❻ RADAR STELLAIRE</div>
      <canvas id="radar-chart" height="200"></canvas>
    </div>

    <!-- ❼ GÉOMÉTRIE SACRÉE -->
    <div class="analysis-block">
      <div class="block-title">❼ GÉOMÉTRIE SACRÉE</div>
      <div class="geo-grid">
        <div class="geo-item"><span class="geo-label">METATRON</span><span class="geo-val" id="geo-metatron">25</span></div>
        <div class="geo-item"><span class="geo-label">FLEUR DE VIE</span><span class="geo-val" id="geo-flower-of-life">30</span></div>
        <div class="geo-item"><span class="geo-label">GRAINE DE VIE</span><span class="geo-val" id="geo-seed-of-life">35</span></div>
        <div class="geo-item"><span class="geo-label">MERKABA</span><span class="geo-val" id="geo-merkaba">20</span></div>
      </div>
    </div>

    <!-- ❽ EGO DISSOLUTION -->
    <div class="analysis-block">
      <div class="block-title">❽ DISSOLUTION DE L'EGO</div>
      <div class="sentiment-track"><div class="sentiment-bar" id="ego-bar" style="width:40%"></div></div>
      <div class="sentiment-row"><span class="sentiment-label">EGO ACTIF</span><span class="sentiment-score" id="ego-score">40/100</span></div>
    </div>

    <!-- ❾ INTENTIONS PURES -->
    <div class="analysis-block">
      <div class="block-title">❾ INTENTIONS PURES</div>
      <div class="tags-wrap" id="intentions-tags"></div>
    </div>

    <!-- ❿ VERBE CRÉATEUR -->
    <div class="analysis-block">
      <div class="block-title">❿ VERBE CRÉATEUR</div>
      <div class="tags-wrap" id="verbe-tags"></div>
    </div>

    <!-- ⓫ ASTROLOGIE COSMIQUE -->
    <div class="analysis-block">
      <div class="block-title">⓫ ASTROLOGIE COSMIQUE</div>
      <div class="astro-grid">
        <div class="astro-item"><span class="astro-label">SIGNE LUNAIRE</span><span class="astro-val" id="astro-lunaire">—</span></div>
        <div class="astro-item"><span class="astro-label">SIGNE SOLAIRE</span><span class="astro-val" id="astro-solaire">—</span></div>
        <div class="astro-item"><span class="astro-label">ASCENDANT</span><span class="astro-val" id="astro-ascendant">—</span></div>
        <div class="astro-item"><span class="astro-label">MAÎTRE NATAL</span><span class="astro-val" id="astro-maitre-natal">—</span></div>
      </div>
    </div>

    <!-- ⓬ MÉTADONNÉES -->
    <div class="analysis-block meta-block">
      <div class="block-title">⓬ MÉTADONNÉES COSMIQUES</div>
      <div class="meta-grid">
        <div><span class="mg-label">PERSONA</span><span class="mg-val" id="meta-persona">—</span></div>
        <div><span class="mg-label">LATENCE</span><span class="mg-val" id="meta-latency">—</span></div>
        <div><span class="mg-label">TOKENS ↑</span><span class="mg-val" id="meta-tin">—</span></div>
        <div><span class="mg-label">TOKENS ↓</span><span class="mg-val" id="meta-tout">—</span></div>
        <div><span class="mg-label">SESSION</span><span class="mg-val" id="meta-session">—</span></div>
        <div><span class="mg-label">HEURE</span><span class="mg-val" id="meta-time">—</span></div>
      </div>
    </div>

  </aside>

</div><!-- /app-shell -->

<!-- Mobile NEXUS toggle -->
<button class="mobile-nexus-btn" id="mobile-nexus-btn" title="Afficher analyses 5D">◉</button>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="script.js?1"></script>
</body>
</html>
