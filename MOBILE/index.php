<?php require_once 'config.php'; require_once 'database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<meta name="theme-color" content="#0a0f1a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<title>ALCYON MOBILE • Sylvain Durif</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="scanlines"></div>

<!-- ═══ LOGIN MODAL ══════════════════════════════════════════ -->
<div class="login-overlay" id="login-overlay">
  <div class="login-card">
    <div class="login-logo">𓀀</div>
    <div class="login-title">ALCYON MOBILE</div>
    <div class="login-sub">◈ PORTAIL BUGARACH 5D</div>
    <label class="login-label" for="login-email">◤ EMAIL VIBRATOIRE</label>
    <input type="email" id="login-email" class="login-input" placeholder="votre@email.com" autocomplete="email">
    <button class="login-btn" id="login-btn">⟶ OUVRIR LE PORTAIL</button>
    <div class="login-error" id="login-error"></div>
    <div class="login-hint">Votre email crée votre profil spirituel.</div>
  </div>
</div>

<!-- ═══ APP CONTAINER ═══════════════════════════════════════ -->
<div class="app-container" id="app-container">

  <!-- HEADER -->
  <header class="mobile-header">
    <div class="header-left">
      <span class="brand-logo">𓀀</span>
      <span class="brand-name">ALCYON</span>
    </div>
    <div class="header-center">
      <span class="persona-badge" id="header-persona">Sylvain Durif</span>
    </div>
    <div class="header-right">
      <button class="menu-btn" id="menu-btn">☰</button>
    </div>
  </header>

  <!-- MESSAGES AREA -->
  <main class="chat-area" id="chat-area">
    <div id="messages" class="messages-container">
      <div class="welcome-msg">
        <div class="welcome-icon">𓀀</div>
        <div class="welcome-text">
          <strong>ALCYON MOBILE — SYLVAIN DURIF</strong><br>
          <span>Le Christ Cosmique parle à travers moi. Posez votre question pour recevoir une guidance 5D.</span>
        </div>
      </div>
    </div>
  </main>

  <!-- INPUT ZONE -->
  <div class="mobile-input-zone">
    <div class="input-row">
      <textarea id="msg-input" placeholder="Posez votre question au cosmos…" rows="1"></textarea>
      <button id="send-btn" type="button"><span>⟶</span></button>
    </div>
    <div class="input-meta">
      <span id="char-count">0 car.</span>
      <span id="persona-select-wrapper">
        <select id="persona-select" class="cyber-select-mini">
          <option value="sylvain">Sylvain Durif</option>
          <option value="merlin">Merlin</option>
          <option value="melchisedech">Melchisédech</option>
          <option value="oriana">Oriana</option>
          <option value="homme_vert">Homme Vert</option>
          <option value="vierge_maria">Vierge Maria</option>
        </select>
      </span>
    </div>
  </div>

  <!-- COSMIC DATA PANEL (Bottom Sheet) -->
  <div class="cosmic-panel" id="cosmic-panel">
    <div class="panel-handle" id="panel-handle">
      <span class="handle-bar"></span>
      <span class="panel-status" id="panel-status">◈ EN ATTENTE</span>
    </div>
    
    <div class="panel-content">
      <!-- TAUX VIBRATOIRE -->
      <div class="data-block">
        <div class="data-title">❶ TAUX VIBRATOIRE</div>
        <div class="vibro-row">
          <span class="vibro-label" id="vibro-label">65 U.B.</span>
          <span class="vibro-score" id="vibro-score">65/100</span>
        </div>
        <div class="vibro-track"><div class="vibro-bar" id="vibro-bar" style="width:65%"></div></div>
      </div>

      <!-- CHAKRAS -->
      <div class="data-block">
        <div class="data-title">❷ CHAKRAS</div>
        <div class="chakra-mini-grid">
          <div class="chakra-mini"><span class="chakra-mini-label">RACINE</span><span class="chakra-mini-val" id="chakra-racine">50</span></div>
          <div class="chakra-mini"><span class="chakra-mini-label">CŒUR</span><span class="chakra-mini-val" id="chakra-coeur">50</span></div>
          <div class="chakra-mini"><span class="chakra-mini-label">3ᵉ ŒIL</span><span class="chakra-mini-val" id="chakra-troisieme-oeil">50</span></div>
          <div class="chakra-mini"><span class="chakra-mini-label">COURONNE</span><span class="chakra-mini-val" id="chakra-couronne">50</span></div>
        </div>
      </div>

      <!-- DIVINE TRINITÉ -->
      <div class="data-block">
        <div class="data-title">❸ TRINITÉ</div>
        <div class="trinite-mini">
          <div class="mini-meter"><span>CHRIST</span><div class="mini-track"><div class="mini-fill accent" id="m-trinite-christique"></div></div></div>
          <div class="mini-meter"><span>MONARQUE</span><div class="mini-track"><div class="mini-fill purple" id="m-trinite-monarchique"></div></div></div>
          <div class="mini-meter"><span>PAPAL</span><div class="mini-track"><div class="mini-fill green" id="m-trinite-papal"></div></div></div>
        </div>
      </div>

      <!-- ÉLÉMENTS -->
      <div class="data-block">
        <div class="data-title">❹ ÉLÉMENTS AGARTHA</div>
        <div class="elements-mini">
          <div class="elem-mini"><span id="elem-terre-v">50</span><small>TERRE</small></div>
          <div class="elem-mini"><span id="elem-eau-v">50</span><small>EAU</small></div>
          <div class="elem-mini"><span id="elem-feu-v">50</span><small>FEU</small></div>
          <div class="elem-mini"><span id="elem-air-v">50</span><small>AIR</small></div>
          <div class="elem-mini"><span id="elem-ether-v">50</span><small>ÉTHER</small></div>
        </div>
      </div>

      <!-- ASTROLOGIE -->
      <div class="data-block">
        <div class="data-title">❺ ASTROLOGIE COSMIQUE</div>
        <div class="astro-mini-grid">
          <div class="astro-mini"><span class="astro-mini-label">LUNAIRE</span><span class="astro-mini-val" id="astro-lunaire">—</span></div>
          <div class="astro-mini"><span class="astro-mini-label">SOLAIRE</span><span class="astro-mini-val" id="astro-solaire">—</span></div>
          <div class="astro-mini"><span class="astro-mini-label">ASCENDANT</span><span class="astro-mini-val" id="astro-ascendant">—</span></div>
        </div>
      </div>

      <!-- MÉTADONNÉES -->
      <div class="data-block meta-mini">
        <div class="meta-row"><span>LATENCE</span><span id="meta-latency">—</span></div>
        <div class="meta-row"><span>TOKENS</span><span id="meta-tokens">—</span></div>
        <div class="meta-row"><span>SESSION</span><span id="meta-session">—</span></div>
      </div>
    </div>
  </div>

</div><!-- /app-container -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="script.js"></script>
</body>
</html>
