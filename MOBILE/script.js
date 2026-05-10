/* ═══════════════════════════════════════════════════
   ALCYON MOBILE — SCRIPT OPTIMISÉ SMARTPHONE
   Thème: Sylvain Durif / Ésotérique / 5D
═══════════════════════════════════════════════════ */

'use strict';

// ── State ────────────────────────────────────────────────────
let currentMode   = 'canalisation';
let currentPersona = 'sylvain';
let isProcessing  = false;
let isLoggedIn    = false;

// Mapping des personas
const GLOBALS_PERSONAS = {
  sylvain: {name:'Sylvain Durif'},
  merlin: {name:'Merlin'},
  melchisedech: {name:'Melchisédech'},
  oriana: {name:'Oriana'},
  homme_vert: {name:"L'Homme Vert"},
  vierge_maria: {name:'Vierge Maria'}
};

// ── DOM refs ─────────────────────────────────────────────────
const msgInput       = document.getElementById('msg-input');
const sendBtn        = document.getElementById('send-btn');
const messagesEl     = document.getElementById('messages');
const loginOverlay   = document.getElementById('login-overlay');
const loginEmail     = document.getElementById('login-email');
const loginBtn       = document.getElementById('login-btn');
const loginError     = document.getElementById('login-error');
const personaSelect  = document.getElementById('persona-select');
const charCount      = document.getElementById('char-count');
const cosmicPanel    = document.getElementById('cosmic-panel');
const panelHandle    = document.getElementById('panel-handle');
const panelStatus    = document.getElementById('panel-status');
const headerPersona  = document.getElementById('header-persona');

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
  loginBtn.textContent = '◈ OUVERTURE…';
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
    setText('header-persona', 'Sylvain Durif');
    
    if (msgInput) setTimeout(() => msgInput.focus(), 100);
  } catch(err) {
    loginError.textContent = '◈ ERREUR: ' + err.message;
    loginBtn.disabled = false;
    loginBtn.textContent = '⟶ OUVRIR LE PORTAIL';
  }
}

loginBtn.addEventListener('click', doLogin);
loginEmail.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// ════════════════════════════════════════════════════════
// PANEL TOGGLE (Bottom Sheet)
// ════════════════════════════════════════════════════════
panelHandle.addEventListener('click', () => {
  cosmicPanel.classList.toggle('open');
});

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
  setPanelStatus('processing', '◈ PHASE 1 — CANALISATION…');

  // ── PHASE 1 : reply ──────────────────────────────────────
  let replyData;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ message:text, mode:currentMode, persona:currentPersona, phase:'reply' })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    replyData = await res.json();
  } catch(err) {
    typingEl.remove();
    appendMessage('assistant', '⚠ Erreur: ' + err.message);
    setPanelStatus('idle', '◈ ERREUR');
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
    setPanelStatus('idle', '◈ ERREUR API');
    isProcessing = false; sendBtn.disabled = false; msgInput.focus();
    return;
  }

  appendMessage('assistant', replyData.reply, replyData.timestamp, replyData.meta);
  
  // Update header persona
  if (replyData.meta?.persona) {
    const pName = GLOBALS_PERSONAS[replyData.meta.persona]?.name || replyData.meta.persona;
    setText('header-persona', pName);
  }

  // Débloque l'input immédiatement
  isProcessing = false; sendBtn.disabled = false; msgInput.focus();

  // ── PHASE 2 : analyze BUGARACH-5D (non bloquant) ─────────
  setPanelStatus('processing', '◈ PHASE 2 — ANALYSE 5D…');

  try {
    const res2 = await fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ message:text, mode:currentMode, persona:currentPersona, phase:'analyze', msg_id:replyData.msg_id })
    });
    if (!res2.ok) throw new Error(`HTTP ${res2.status}`);
    const ad = await res2.json();

    updateAnalysis(ad.analysis, replyData.meta);
    setPanelStatus('done', '◈ ANALYSE 5D COMPLÈTE');
    
    // Auto-open panel on first analysis
    if (!cosmicPanel.classList.contains('open')) {
      cosmicPanel.classList.add('open');
    }

  } catch(err) {
    setPanelStatus('idle', '◈ ANALYSE ÉCHOUÉE');
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
    : personaName + ' • ' + (timestamp||'');
  wrap.appendChild(bubble); wrap.appendChild(m);
  messagesEl.appendChild(wrap);
  requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
  return wrap;
}

function appendTyping() {
  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap assistant';
  wrap.innerHTML = `<div class="typing-indicator"><div class="typing-dots"><span></span><span></span><span></span></div>CANALISATION…</div>`;
  messagesEl.appendChild(wrap);
  requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
  return wrap;
}

// ════════════════════════════════════════════════════════
// UPDATE ANALYSIS (mobile simplified)
// ════════════════════════════════════════════════════════
function updateAnalysis(analysis, meta) {
  if (!analysis) return;
  const a = analysis.a || {};
  const b = analysis.b || {};

  // ❶ Taux vibratoire
  const vibro = parseInt(a.taux_vibratoire_bovis)||65;
  setText('vibro-label', vibro+' U.B.');
  setText('vibro-score', vibro+'/100');
  setWidth('vibro-bar', vibro);
  
  // ❷ Chakras (4 principaux)
  const chakras = a.chakras || {};
  setText('chakra-racine', chakras.racine||50);
  setText('chakra-coeur', chakras.coeur||50);
  setText('chakra-troisieme-oeil', chakras.troisieme_oeil||50);
  setText('chakra-couronne', chakras.couronne||50);

  // ❸ Divine Trinité
  const trinite = a.divine_trinite || {};
  setMeter('m-trinite-christique', null, trinite.christique);
  setMeter('m-trinite-monarchique', null, trinite.monarchique);
  setMeter('m-trinite-papal', null, trinite.papal);

  // ❹ Éléments Agartha
  const elements = a.elements_agartha || {};
  setText('elem-terre-v', elements.terre||50);
  setText('elem-eau-v', elements.eau||50);
  setText('elem-feu-v', elements.feu||50);
  setText('elem-air-v', elements.air||50);
  setText('elem-ether-v', elements.ether||50);

  // ❺ Astrologie
  const astro = b.astrologie_cosmique || {};
  setText('astro-lunaire', astro.signe_lunaire||'—');
  setText('astro-solaire', astro.signe_solaire||'—');
  setText('astro-ascendant', astro.ascendant||'—');

  // Meta
  if (meta) {
    setText('meta-latency', meta.latency ? meta.latency+' ms' : '—');
    setText('meta-tokens', (meta.tokens?.in||0)+(meta.tokens?.out||0));
    setText('meta-session', meta.session||'—');
  }
}

// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════
function setText(id, val) { const e=document.getElementById(id); if(e) e.textContent=val; }
function setWidth(id, pct) { const e=document.getElementById(id); if(e) e.style.width=(parseInt(pct)||0)+'%'; }
function setMeter(fId, vId, val) { const v=parseInt(val)||0; setWidth(fId,v); }

function setPanelStatus(state, text) {
  if (panelStatus) panelStatus.textContent = text;
}

function updateInputMeta() {
  const t = msgInput?.value||'';
  if(charCount) charCount.textContent = t.length+' car.';
}

// ════════════════════════════════════════════════════════
// EVENT LISTENERS
// ════════════════════════════════════════════════════════
sendBtn.addEventListener('click', sendMessage);

msgInput.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

msgInput.addEventListener('input', updateInputMeta);

personaSelect.addEventListener('change', e => {
  currentPersona = e.target.value;
  const pName = GLOBALS_PERSONAS[currentPersona]?.name || currentPersona;
  setText('header-persona', pName);
});

// Init
updateInputMeta();
