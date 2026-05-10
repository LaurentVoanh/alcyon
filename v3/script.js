/* ═══════════════════════════════════════════════════
   AETHER/ALCYON v5.0 — PORTAIL ULTIME — SCRIPT COMPLET
   Fusion V1 (Panopticon) + V2 (Bugarach 5D) + Améliorations
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
const analysisPanel  = document.getElementById('analysis-panel');

// Mapping des personas
const GLOBALS_PERSONAS = {
  sylvain: {name:'Sylvain Durif', title:'Christ Cosmique'},
  merlin: {name:'Merlin', title:'Enchanteur'},
  melchisedech: {name:'Melchisédech', title:'Roi de Salem'},
  oriana: {name:'Oriana', title:'Gardienne Stellaire'},
  homme_vert: {name:"L'Homme Vert", title:'Esprit Nature'},
  vierge_maria: {name:'Vierge Maria', title:'Mère Divine'}
};

// ════════════════════════════════════════════════════════
// LOGIN
// ════════════════════════════════════════════════════════
async function doLogin() {
  const email = loginEmail.value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    loginError.textContent = '◈ Email vibratoire invalide';
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
    document.getElementById('app-shell').classList.add('active');
    
    setText('user-email-display', data.email);
    setText('user-since', 'depuis ' + (data.member_since||'').substring(0,10));
    setText('sid-display', data.sid || '—');
    const initial = data.email.charAt(0).toUpperCase();
    setText('user-avatar', initial);

    // Générer token CSRF
    if (data.csrf_token) {
      sessionStorage.setItem('csrf_token', data.csrf_token);
    }

    if (msgInput) msgInput.focus();
    initCharts();
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
        datasets: [{ 
          data:[0,0,0,0,0], 
          backgroundColor:'rgba(124,58,237,.07)', 
          borderColor:'rgba(124,58,237,.55)', 
          pointBackgroundColor:'#7c3aed', 
          pointRadius:3, 
          borderWidth:1.5 
        }]
      },
      options: { 
        ...defs, 
        scales: { 
          r: { 
            min:0, max:100, 
            grid:{color:'rgba(255,255,255,.05)'}, 
            angleLines:{color:'rgba(255,255,255,.05)'}, 
            ticks:{display:false}, 
            pointLabels:{color:'#4a5a80',font:{family:'Share Tech Mono',size:8}} 
          } 
        } 
      }
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
    const csrfToken = sessionStorage.getItem('csrf_token');
    const res = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ 
        message:text, 
        mode:currentMode, 
        persona:currentPersona, 
        phase:'reply',
        csrf_token: csrfToken
      })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status} — ${res.statusText}`);
    replyData = await res.json();
  } catch(err) {
    typingEl.remove();
    appendMessage('assistant', '⚠ Erreur réseau phase 1: ' + err.message);
    setAnalysisStatus('error', '◈ ERREUR — ' + err.message);
    isProcessing = false; 
    sendBtn.disabled = false; 
    msgInput.focus();
    return;
  }

  typingEl.remove();
  appendMessage('assistant', replyData.reply);
  
  const msgId = replyData.msg_id;
  const meta = replyData.meta || {};
  
  updateSidebar(meta);
  setAnalysisStatus('complete', '◈ RÉPONSE REÇUE — PHASE 2 EN COURS…');

  // ── PHASE 2 : analyze (lancée en background) ─────────────
  setTimeout(() => {
    runAnalysis(text, msgId);
  }, 100);

  isProcessing = false;
  sendBtn.disabled = false;
  msgInput.focus();
}

// ════════════════════════════════════════════════════════
// ANALYSIS RUNNER
// ════════════════════════════════════════════════════════
async function runAnalysis(text, msgId) {
  setAnalysisStatus('processing', '◈ PHASE 2 — ANALYSE 5D EN COURS…');
  
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ message:text, msg_id:msgId, phase:'analyze' })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    
    if (data.analysis) {
      updateAnalysis(data.analysis, data.stats);
      setAnalysisStatus('complete', '◈ ANALYSE TERMINÉE');
    }
  } catch(err) {
    console.error('Analyse error:', err);
    setAnalysisStatus('error', '◈ ERREUR ANALYSE — ' + err.message);
  }
}

// ════════════════════════════════════════════════════════
// UPDATE ANALYSIS (mapping complet)
// ════════════════════════════════════════════════════════
function updateAnalysis(analysis, stats) {
  if (!analysis) return;
  const a = analysis.a || {};
  const b = analysis.b || {};

  // ❶ Vecteur émotionnel
  const sentiment = a.sentiment || 'neutre';
  const score = parseInt(a.sentiment_score) || 50;
  setText('sentiment-label', sentiment.toUpperCase());
  setText('sentiment-score', score+'/100');
  setWidth('sentiment-bar', score);
  setText('emotion-primary', a.emotion_primary || '—');
  setText('emotion-secondary', a.emotion_secondary || '—');
  setText('tone-val', a.tone || '—');

  // ❷ Taux vibratoire
  const vibro = parseInt(a.taux_vibratoire_bovis) || 65;
  setText('vibro-label', vibro+' U.B.');
  setText('vibro-score', vibro+'/100');
  setWidth('vibro-bar', vibro);
  setText('aura-val', (a.aura_couleur||'indéterminée') + ' — ' + (a.aura_taille||'moyenne'));

  // ❸ Chakras
  const chakras = a.chakras || {};
  setMeter('m-chakra-racine', 'mv-chakra-racine', chakras.racine);
  setMeter('m-chakra-sacre', 'mv-chakra-sacre', chakras.sacre);
  setMeter('m-chakra-plexus', 'mv-chakra-plexus', chakras.plexus);
  setMeter('m-chakra-coeur', 'mv-chakra-coeur', chakras.coeur);
  setMeter('m-chakra-gorge', 'mv-chakra-gorge', chakras.gorge);
  setMeter('m-chakra-troisieme-oeil', 'mv-chakra-troisieme-oeil', chakras.troisieme_oeil);
  setMeter('m-chakra-couronne', 'mv-chakra-couronne', chakras.couronne);

  // ❹ Trinité Divine
  const trinite = a.divine_trinite || {};
  setMeter('m-trinite-christique', 'mv-trinite-christique', trinite.christique);
  setMeter('m-trinite-monarchique', 'mv-trinite-monarchique', trinite.monarchique);
  setMeter('m-trinite-papal', 'mv-trinite-papal', trinite.papal);

  // ❺ Forces spirituelles
  setMeter('m-emprise-reptilienne', 'mv-emprise-reptilienne', a.emprise_reptilienne);
  setMeter('m-kvorz-level', 'mv-kvorz-level', a.kvorz_level);
  setMeter('m-eveil-conscience', 'mv-eveil-conscience', a.eveil_conscience);

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
  const ego = parseInt(b.ego_dissolution) || 40;
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

  // Flash blocks
  document.querySelectorAll('.analysis-block').forEach(el => {
    el.classList.remove('updated'); 
    void el.offsetWidth; 
    el.classList.add('updated');
  });
}

// ════════════════════════════════════════════════════════
// DOM HELPERS
// ════════════════════════════════════════════════════════
function setText(id, val) { 
  const e = document.getElementById(id); 
  if(e) e.textContent = val; 
}

function setWidth(id, pct) { 
  const e = document.getElementById(id); 
  if(e) e.style.width = (parseInt(pct)||0) + '%'; 
}

function setMeter(fId, vId, val) { 
  const v = parseInt(val) || 0; 
  setWidth(fId, v); 
  if(vId) setText(vId, v); 
}

function renderTags(cId, items, cls) {
  const c = document.getElementById(cId);
  if (!c) return;
  c.innerHTML = '';
  if (!Array.isArray(items) || !items.length) {
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

function updateSidebar(meta) {
  if (meta?.latency) setText('latence-astro', meta.latency+' ms');
  setText('meta-persona', GLOBALS_PERSONAS[meta?.persona]?.name || meta?.persona || '—');
  setText('meta-latency', meta?.latency ? meta.latency+' ms' : '—');
  setText('meta-tin', meta?.tokens?.in||'—');
  setText('meta-tout', meta?.tokens?.out||'—');
  setText('meta-session', meta?.session||'—');
  setText('meta-time', new Date().toLocaleTimeString('fr-FR'));
}

function updateInputMeta() {
  const t = msgInput?.value || '';
  const w = t.trim() ? t.trim().split(/\s+/).length : 0;
  if(charCount) charCount.textContent = t.length+' car.';
  if(wordCountEl) wordCountEl.textContent = w+' mots';
}

// ════════════════════════════════════════════════════════
// APPEND MESSAGE
// ════════════════════════════════════════════════════════
function appendMessage(role, content) {
  const div = document.createElement('div');
  div.className = 'message '+role;
  
  const avatar = document.createElement('div');
  avatar.className = 'message-avatar';
  avatar.textContent = role === 'user' ? '👤' : '𓀀';
  
  const bubble = document.createElement('div');
  bubble.className = 'message-content';
  bubble.textContent = content;
  
  div.appendChild(avatar);
  div.appendChild(bubble);
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function appendTyping() {
  const div = document.createElement('div');
  div.className = 'message assistant typing-indicator';
  div.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return div;
}

// ════════════════════════════════════════════════════════
// MODE & PERSONA SELECTORS
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
    const personaData = GLOBALS_PERSONAS[currentPersona];
    setText('chat-persona-label', personaData?.name || currentPersona);
  });
}

// ════════════════════════════════════════════════════════
// CLEAR SESSION
// ════════════════════════════════════════════════════════
if (clearBtn) {
  clearBtn.addEventListener('click', async () => {
    if (!isLoggedIn || !confirm('◈ PURIFIER LA SESSION ?')) return;
    try {
      await fetch('clear.php', {method:'POST'});
      location.reload();
    } catch(err) {
      alert('Erreur: '+err.message);
    }
  });
}

// ════════════════════════════════════════════════════════
// KEYBOARD SHORTCUTS
// ════════════════════════════════════════════════════════
if (msgInput) {
  msgInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
}

// Auto-update time
setInterval(() => {
  setText('chat-time', new Date().toLocaleTimeString('fr-FR'));
}, 1000);

// Load history/system on demand
async function loadHistory() {
  const content = document.getElementById('history-content');
  if (!content || !isLoggedIn) return;
  content.innerHTML = '<div class="section-idle"><div class="section-idle-icon">◎</div><div>Chargement...</div></div>';
  // TODO: Fetch from API
}

async function loadCognitiveAnalysis() {
  const content = document.getElementById('cognitive-content');
  if (!content || !isLoggedIn) return;
  // TODO: Load latest analyses
}

async function loadSystem() {
  const content = document.getElementById('system-content');
  if (!content || !isLoggedIn) return;
  content.innerHTML = '<div class="section-idle"><div class="section-idle-icon">⬟</div><div>Diagnostics en cours...</div></div>';
  // TODO: System status check
}

// Init
console.log('◈ AETHER/ALCYON v5.0 — PORTAIL ULTIME INITIALISÉ');
