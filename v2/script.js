/* ═══════════════════════════════════════════════════
   ALCYON v4.0 — PORTAIL DE BUGARACH — SCRIPT COMPLET
   Thème: Sylvain Durif / Ésotérique / 5D
═══════════════════════════════════════════════════ */

'use strict';

// ── State ────────────────────────────────────────────────────
let currentMode   = 'canalisation';
let currentPersona = 'sylvain';
let totalMantras  = 0;
let totalPrieres  = 0;
let radarChart    = null;
let isProcessing  = false;
let isLoggedIn    = false;
let allAnalyses   = [];

// ── DOM refs ─────────────────────────────────────────────────
const msgInput       = document.getElementById('msg-input');
const sendBtn        = document.getElementById('send-btn');
const messagesEl     = document.getElementById('messages');
const clearBtn       = document.getElementById('clear-btn');
const personaSelect  = document.getElementById('persona-select');
const charCount      = document.getElementById('char-count');
const wordCountEl    = document.getElementById('word-count-input');
const loginOverlay   = document.getElementById('login-overlay');
const loginEmail     = document.getElementById('login-email');
const loginBtn       = document.getElementById('login-btn');
const loginError     = document.getElementById('login-error');
const mobileBtn      = document.getElementById('mobile-nexus-btn');
const analysisPanel  = document.getElementById('analysis-panel');

// ════════════════════════════════════════════════════════
// LOGIN
// ════════════════════════════════════════════════════════
async function doLogin() {
  const email = loginEmail.value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    loginError.textContent = '◈ Email vibratoire invalide — vérifiez le format';
    loginEmail.focus();
    return;
  }
  loginBtn.disabled = true;
  loginBtn.textContent = '◈ OUVERTURE DU PORTAIL…';
  loginError.textContent = '';

  try {
    const res  = await fetch('login.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email})
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    isLoggedIn = true;
    loginOverlay.classList.add('hidden');
    setText('user-email-display', data.email);
    setText('user-since', 'depuis ' + (data.member_since||'').substring(0,10));
    setText('sid-display', data.sid || '—');
    const initial = data.email.charAt(0).toUpperCase();
    setText('user-avatar', initial);

    if (msgInput) msgInput.focus();
  } catch(err) {
    loginError.textContent = '◈ ERREUR: ' + err.message;
    loginBtn.disabled = false;
    loginBtn.textContent = '⟶ OUVRIR LE PORTAIL';
  }
}

loginBtn.addEventListener('click', doLogin);
loginEmail.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// ════════════════════════════════════════════════════════
// NAVIGATION
// ════════════════════════════════════════════════════════
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', e => {
    e.preventDefault();
    if (!isLoggedIn) return;
    switchSection(item.dataset.section);
  });
});

function switchSection(section) {
  document.querySelectorAll('.nav-item').forEach(i => i.classList.toggle('active', i.dataset.section === section));
  document.querySelectorAll('.section-panel').forEach(p => p.classList.toggle('active', p.id === 'section-' + section));
  if (section === 'history')  loadHistory();
  if (section === 'analysis') loadCognitiveAnalysis();
  if (section === 'system')   loadSystem();
}

// ════════════════════════════════════════════════════════
// CHART - RADAR STELLAIRE
// ════════════════════════════════════════════════════════
function initCharts() {
  const defs = { animation:{duration:900}, plugins:{legend:{display:false}} };

  const ctxR = document.getElementById('radar-chart');
  if (ctxR) {
    radarChart = new Chart(ctxR.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['ANDROMÈDE','PLÉIADES','SIRIUS','ARCTURUS','ORION'],
        datasets: [{ data:[0,0,0,0,0], backgroundColor:'rgba(124,58,237,.07)', borderColor:'rgba(124,58,237,.55)', pointBackgroundColor:'#7c3aed', pointRadius:3, borderWidth:1.5 }]
      },
      options: { ...defs, scales: { r: { min:0,max:100, grid:{color:'rgba(255,255,255,.05)'}, angleLines:{color:'rgba(255,255,255,.05)'}, ticks:{display:false}, pointLabels:{color:'#4a5a80',font:{family:'Share Tech Mono',size:8}} } } }
    });
  }
}

// ════════════════════════════════════════════════════════
// SEND MESSAGE (2 phases)
// ════════════════════════════════════════════════════════
async function sendMessage() {
  if (isProcessing || !isLoggedIn) return;
  const text = msgInput.value.trim();
  if (!text) return;

  isProcessing = true;
  sendBtn.disabled = true;
  msgInput.value = '';
  updateInputMeta();

  appendMessage('user', text);

  const typingEl = appendTyping();
  setAnalysisStatus('processing', '◈ PHASE 1 — CANALISATION…');

  // ── PHASE 1 : reply ──────────────────────────────────────
  let replyData;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ message:text, mode:currentMode, persona:currentPersona, phase:'reply' })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status} — ${res.statusText}`);
    replyData = await res.json();
  } catch(err) {
    typingEl.remove();
    appendMessage('assistant', '⚠ Erreur réseau phase 1: ' + err.message);
    setAnalysisStatus('idle', '◈ ERREUR — ' + err.message);
    isProcessing = false; sendBtn.disabled = false; msgInput.focus();
    return;
  }

  typingEl.remove();

  if (replyData.error === 'SESSION_EXPIRED') {
    loginOverlay.classList.remove('hidden');
    isProcessing = false; sendBtn.disabled = false;
    return;
  }

  if (replyData.error) {
    appendMessage('assistant', '⚠ ' + replyData.error);
    setAnalysisStatus('idle', '◈ ERREUR API — ' + replyData.error);
    isProcessing = false; sendBtn.disabled = false; msgInput.focus();
    return;
  }

  appendMessage('assistant', replyData.reply, replyData.timestamp, replyData.meta);
  updateSidebar(replyData.meta, {});

  // Débloque l'input immédiatement
  isProcessing = false; sendBtn.disabled = false; msgInput.focus();

  // ── PHASE 2 : analyze BUGARACH-5D (non bloquant) ─────────────────────
  setAnalysisStatus('processing', '◈ PHASE 2 — ANALYSE VIBRATOIRE 5D…');

  try {
    const res2 = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ message:text, mode:currentMode, persona:currentPersona, phase:'analyze', msg_id:replyData.msg_id })
    });
    if (!res2.ok) throw new Error(`HTTP ${res2.status}`);
    const ad = await res2.json();

    updateAnalysis(ad.analysis, replyData.meta);
    updateSidebar(replyData.meta, ad.stats);
    setAnalysisStatus('done', '◈ ANALYSE 5D COMPLÈTE — ' + ad.timestamp);
    allAnalyses.push({ ts:ad.timestamp, text, analysis:ad.analysis });

  } catch(err) {
    setAnalysisStatus('idle', '◈ ANALYSE 5D ÉCHOUÉE — ' + err.message);
  }
}

// ════════════════════════════════════════════════════════
// MESSAGES DOM
// ════════════════════════════════════════════════════════
function appendMessage(role, text, timestamp, meta) {
  const wrap   = document.createElement('div');
  wrap.className = 'msg-wrap ' + role;
  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  bubble.textContent = text;
  const m = document.createElement('div');
  m.className = 'msg-meta';
  const personaName = meta?.persona ? (GLOBALS_PERSONAS[meta.persona]?.name || meta.persona) : 'ALCYON';
  m.textContent = role === 'user'
    ? 'VOUS • ' + new Date().toLocaleTimeString('fr-FR')
    : personaName + ' • ' + (timestamp||'') + ' • ' + (meta?.model||'');
  wrap.appendChild(bubble); wrap.appendChild(m);
  messagesEl.appendChild(wrap);
  requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
  return wrap;
}

function appendTyping() {
  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap assistant';
  wrap.innerHTML = `<div class="typing-indicator"><div class="typing-dots"><span></span><span></span><span></span></div>CANALISATION EN COURS…</div>`;
  messagesEl.appendChild(wrap);
  requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
  return wrap;
}

// Mapping des personas
const GLOBALS_PERSONAS = {
  sylvain: {name:'Sylvain Durif'},
  merlin: {name:'Merlin'},
  melchisedech: {name:'Melchisédech'},
  oriana: {name:'Oriana'},
  homme_vert: {name:"L'Homme Vert"},
  vierge_maria: {name:'Vierge Maria'}
};

// ════════════════════════════════════════════════════════
// UPDATE ANALYSIS (mapping complet 12 blocs BUGARACH-5D)
// ════════════════════════════════════════════════════════
function updateAnalysis(analysis, meta) {
  if (!analysis) return;
  const a = analysis.a || {};
  const b = analysis.b || {};

  // ❶ Taux vibratoire + Chakras + Aura
  const vibro = parseInt(a.taux_vibratoire_bovis)||65;
  setText('vibro-label', vibro+' U.B.');
  setText('vibro-score', vibro+'/100');
  setWidth('vibro-bar', vibro);
  
  const chakras = a.chakras || {};
  setText('chakra-racine', chakras.racine||50);
  setText('chakra-sacre', chakras.sacre||50);
  setText('chakra-plexus', chakras.plexus||50);
  setText('chakra-coeur', chakras.coeur||50);
  setText('chakra-gorge', chakras.gorge||50);
  setText('chakra-troisieme-oeil', chakras.troisieme_oeil||50);
  setText('chakra-couronne', chakras.couronne||50);
  setText('aura-val', (a.aura_couleur||'indéterminée') + ' — ' + (a.aura_taille||'moyenne'));

  // ❷ Divine Trinité
  const trinite = a.divine_trinite || {};
  setMeter('m-trinite-christique', 'mv-trinite-christique', trinite.christique);
  setMeter('m-trinite-monarchique', 'mv-trinite-monarchique', trinite.monarchique);
  setMeter('m-trinite-papal', 'mv-trinite-papal', trinite.papal);

  // ❸ Emprise Reptilienne / Kvorz / Éveil
  setMeter('m-emprise-reptilienne', 'mv-emprise-reptilienne', a.emprise_reptilienne);
  setMeter('m-kvorz-level', 'mv-kvorz-level', a.kvorz_level);
  setMeter('m-eveil-conscience', 'mv-eveil-conscience', a.eveil_conscience);

  // ❹ Éléments Agartha
  const elements = a.elements_agartha || {};
  setBar('elem-terre', 'elem-terre-v', elements.terre);
  setBar('elem-eau', 'elem-eau-v', elements.eau);
  setBar('elem-feu', 'elem-feu-v', elements.feu);
  setBar('elem-air', 'elem-air-v', elements.air);
  setBar('elem-ether', 'elem-ether-v', elements.ether);

  // ❺ Status Évacuation
  setText('status-evacuation', (a.status_evacuation_fin_des_temps||'EN ATTENTE').toUpperCase());

  // ❻ Radar Stellaire
  const radar = b.radar_stellaire || {};
  if (radarChart) {
    radarChart.data.datasets[0].data = [
      radar.andromede||0, radar.pleiades||0, radar.sirius||0, radar.arcturus||0, radar.orion||0
    ];
    radarChart.update();
  }

  // ❼ Géométrie Sacrée
  const geo = b.geometrie_sacree || {};
  setText('geo-metatron', geo.metatron||25);
  setText('geo-flower-of-life', geo.flower_of_life||30);
  setText('geo-seed-of-life', geo.seed_of_life||35);
  setText('geo-merkaba', geo.merkaba||20);

  // ❽ Ego Dissolution
  const ego = parseInt(b.ego_dissolution)||40;
  setWidth('ego-bar', ego);
  setText('ego-score', ego+'/100');

  // ❾ Intentions Pures
  renderTags('intentions-tags', b.intentions_pures||[], 'tag-theme');

  // ❿ Verbe Créateur
  renderTags('verbe-tags', b.verbe_createur||[], 'tag-keyword');

  // ⓫ Astrologie Cosmique
  const astro = b.astrologie_cosmique || {};
  setText('astro-lunaire', astro.signe_lunaire||'—');
  setText('astro-solaire', astro.signe_solaire||'—');
  setText('astro-ascendant', astro.ascendant||'—');
  setText('astro-maitre-natal', astro.maitre_natal||'—');

  // ⓬ Meta
  if (meta) {
    setText('meta-persona', GLOBALS_PERSONAS[meta.persona]?.name || meta.persona || '—');
    setText('meta-latency', meta.latency ? meta.latency+' ms' : '—');
    setText('meta-tin', meta.tokens?.in||'—');
    setText('meta-tout', meta.tokens?.out||'—');
    setText('meta-session', meta.session||'—');
    setText('meta-time', new Date().toLocaleTimeString('fr-FR'));
  }

  // Flash blocks
  document.querySelectorAll('.analysis-block').forEach(el => {
    el.classList.remove('updated'); void el.offsetWidth; el.classList.add('updated');
  });
}

// ════════════════════════════════════════════════════════
// DOM HELPERS
// ════════════════════════════════════════════════════════
function setText(id, val) { const e=document.getElementById(id); if(e) e.textContent=val; }
function setWidth(id, pct) { const e=document.getElementById(id); if(e) e.style.width=(parseInt(pct)||0)+'%'; }
function setBar(fId, vId, val) { const v=parseInt(val)||0; setWidth(fId,v); setText(vId,v); }
function setMeter(fId, vId, val) { const v=parseInt(val)||0; setWidth(fId,v); if(vId) setText(vId,v); }

function renderTags(cId, items, cls) {
  const c = document.getElementById(cId);
  if (!c) return;
  c.innerHTML = '';
  if (!Array.isArray(items)||!items.length) {
    c.innerHTML = '<span style="font-size:.58rem;color:#2a3550;font-family:\'Share Tech Mono\',monospace">—</span>';
    return;
  }
  items.slice(0,7).forEach((item,i) => {
    const t = document.createElement('span');
    t.className = 'tag '+cls;
    t.textContent = item;
    t.style.animationDelay = (i*40)+'ms';
    c.appendChild(t);
  });
}

function setAnalysisStatus(state, text) {
  const e = document.getElementById('analysis-status');
  if (e) e.innerHTML = `<span class="status-${state}">${text}</span>`;
}

function updateSidebar(meta, stats) {
  if (stats) {
    totalMantras += stats.mantras_count || 0;
    totalPrieres += stats.prieres_count || 0;
  }
  setText('total-mantras', totalMantras);
  setText('total-prieres', totalPrieres);
  setText('latence-astro', meta?.latency ? meta.latency+' ms' : '—');
  if (stats?.karma_score !== undefined) setText('karma-score', stats.karma_score);
}

function updateInputMeta() {
  const t = msgInput?.value||'';
  const w = t.trim() ? t.trim().split(/\s+/).length : 0;
  if(charCount)   charCount.textContent   = t.length+' car.';
  if(wordCountEl) wordCountEl.textContent = w+' mots';
}

// ════════════════════════════════════════════════════════
// PAGES
// ════════════════════════════════════════════════════════
function showLoading(cId, icon, label) {
  const c = document.getElementById(cId);
  if (!c) return;
  c.innerHTML = `<div class="section-loading">
    <div class="loading-icon">${icon}</div>
    <div class="loading-text">${label}<br><span class="loading-dots"><span></span><span></span><span></span></span></div>
  </div>`;
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function loadHistory() {
  showLoading('history-content', '◎', 'CHARGEMENT ARCHIVES AKASHIQUES');
  try {
    const data = await (await fetch('history.php')).json();
    const c = document.getElementById('history-content');
    if (!data.messages?.length) {
      c.innerHTML = '<div class="section-idle"><div class="section-idle-icon">◎</div><div class="section-idle-title">ARCHIVES VIDES</div><div class="section-idle-sub">Aucune canalisation dans cette session.</div></div>';
      return;
    }
    c.innerHTML = data.messages.map(m => `
      <div class="history-row ${m.role}">
        <div class="history-role">${m.role==='user'?'VOUS':'ENTITÉ'}</div>
        <div class="history-content">${escHtml(m.content)}</div>
        <div class="history-meta">${m.created_at||''} • ${m.model_used||''} • ${((m.tokens_in||0)+(m.tokens_out||0))} tok</div>
      </div>`).join('');
  } catch(e) {
    document.getElementById('history-content').innerHTML = `<div class="error-msg">ERREUR: ${escHtml(e.message)}</div>`;
  }
}

async function loadCognitiveAnalysis() {
  showLoading('cognitive-content', '◉', 'CHARGEMENT PROFILS VIBRATOIRES');
  try {
    const data = await (await fetch('stats.php')).json();
    const c = document.getElementById('cognitive-content');
    if (!data.profiles?.length) {
      c.innerHTML = '<div class="section-idle"><div class="section-idle-icon">◉</div><div class="section-idle-title">AUCUN PROFIL</div><div class="section-idle-sub">Démarrez des canalisation pour générer des profils.</div></div>';
      return;
    }
    c.innerHTML = `
      <div class="bb-header">◈ ARCHIVES AKASHIQUES — ${data.profiles.length} SESSION(S) ANALYSÉE(S)</div>
      <div class="profiles-grid">
      ${data.profiles.map((p,i) => `
        <div class="profile-card">
          <div class="profile-header">
            <span class="profile-sid">SESSION #${i+1} — ${escHtml((p.session_id||'').substring(0,10))}</span>
            <span class="profile-count">${p.msg_count} msgs • ${p.total_tokens||0} tok</span>
          </div>
          <div class="profile-meters">
            <div class="pm-item"><span>VIBRATION</span><div class="meter-track sm"><div class="meter-fill green" style="width:${Math.round(p.avg_vibro||50)}%"></div></div><span>${Math.round(p.avg_vibro||50)}</span></div>
            <div class="pm-item"><span>ÉVEIL</span><div class="meter-track sm"><div class="meter-fill accent" style="width:${Math.round(p.avg_eveil||50)}%"></div></div><span>${Math.round(p.avg_eveil||50)}</span></div>
            <div class="pm-item"><span>KARMA</span><div class="meter-track sm"><div class="meter-fill warn" style="width:${Math.round(p.avg_karma||50)}%"></div></div><span>${Math.round(p.avg_karma||50)}</span></div>
          </div>
        </div>`).join('')}
      </div>`;
  } catch(e) {
    document.getElementById('cognitive-content').innerHTML = `<div class="error-msg">ERREUR: ${escHtml(e.message)}</div>`;
  }
}

async function loadSystem() {
  showLoading('system-content', '⬟', 'DIAGNOSTICS DU PORTAIL EN COURS');
  try {
    const d = await (await fetch('system.php')).json();
    document.getElementById('system-content').innerHTML = `
      <div class="sys-grid">
        <div class="sys-item"><span class="sys-label">PHP</span><span class="sys-val">${d.php||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">SERVEUR</span><span class="sys-val">${d.server||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">MEM LIMIT</span><span class="sys-val">${d.memory_limit||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">MAX EXEC</span><span class="sys-val">${d.max_exec||'—'}s</span></div>
        <div class="sys-item"><span class="sys-label">DB SIZE</span><span class="sys-val">${d.db_size||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">SESSIONS</span><span class="sys-val">${d.total_sessions||0}</span></div>
        <div class="sys-item"><span class="sys-label">MESSAGES</span><span class="sys-val">${d.total_messages||0}</span></div>
        <div class="sys-item"><span class="sys-label">ANALYSES</span><span class="sys-val">${d.total_analyses||0}</span></div>
        <div class="sys-item"><span class="sys-label">CLÉS VALIDES</span><span class="sys-val">${d.keys_count||0}/3</span></div>
        <div class="sys-item"><span class="sys-label">MOD. CHAT</span><span class="sys-val">${d.model_chat||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">MOD. ANALYSE</span><span class="sys-val">${d.model_analysis||'—'}</span></div>
        <div class="sys-item"><span class="sys-label">DATE</span><span class="sys-val">${d.uptime||'—'}</span></div>
      </div>
      <div class="sys-keys-status">
        ${(d.key_status||[]).map(k=>`<div class="key-row-sys"><span class="dot ${k.ok?'dot-green':'dot-red'}"></span>${escHtml(k.role)} — ${k.ok?'OPÉRATIONNELLE':'INVALIDE'}</div>`).join('')}
      </div>`;
  } catch(e) {
    document.getElementById('system-content').innerHTML = `<div class="error-msg">ERREUR: ${escHtml(e.message)}</div>`;
  }
}

// ════════════════════════════════════════════════════════
// CONTRÔLES
// ════════════════════════════════════════════════════════
document.querySelectorAll('.mode-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentMode = btn.dataset.mode;
    setText('chat-mode-label', currentMode.toUpperCase());
  });
});

if (personaSelect) {
  personaSelect.addEventListener('change', () => {
    currentPersona = personaSelect.value;
    const selectedText = personaSelect.options[personaSelect.selectedIndex].text.split('—')[0].trim();
    setText('chat-persona-label', selectedText);
  });
}

if (msgInput) {
  msgInput.addEventListener('input', updateInputMeta);
  msgInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
}
if (sendBtn) sendBtn.addEventListener('click', sendMessage);

if (clearBtn) {
  clearBtn.addEventListener('click', async () => {
    if (!confirm('Purifier cette session ?')) return;
    try { await fetch('clear.php',{method:'POST'}); } catch(e){}
    messagesEl.innerHTML = `<div class="welcome-msg"><div class="welcome-icon">𓀀</div><div class="welcome-text"><strong>SESSION PURIFIÉE</strong><br><span>Nouvelle session démarrée.</span></div></div>`;
    totalMantras=0; totalPrieres=0; allAnalyses=[];
    updateSidebar({},{}); setAnalysisStatus('idle','◈ EN ATTENTE');
  });
}

// Mobile NEXUS toggle
if (mobileBtn) {
  mobileBtn.addEventListener('click', () => {
    const open = analysisPanel.classList.toggle('mobile-open');
    mobileBtn.classList.toggle('active', open);
    mobileBtn.textContent = open ? '✕' : '◉';
  });
}

// Horloge
setInterval(() => setText('chat-time', new Date().toLocaleTimeString('fr-FR')), 1000);
setText('chat-time', new Date().toLocaleTimeString('fr-FR'));

// ════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════
initCharts();
loginEmail.focus();
