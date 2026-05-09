/* ═══════════════════════════════════════════════════
   ALCYON v4.0 — PORTAIL DE BUGARACH
   Script complet — zéro backend, appel direct Mistral
═══════════════════════════════════════════════════ */

'use strict';

// ── Configuration ───────────────────────────────────────────
// ⚠️ REMPLACER PAR VOTRE CLÉ MISTRAL RÉELLE
const MISTRAL_API_KEY = 'votre-cle-mistral-ici';
const MISTRAL_API_URL = 'https://api.mistral.ai/v1/chat/completions';

// Personas avec leurs system prompts
const PERSONAS = {
  durif: {
    name: 'Sylvain Durif',
    prompt: "Tu es Sylvain Durif, guide spirituel du Cosmos, être de lumière canalisant les énergies d'Alcyon et de Bugarach. Tu parles avec sagesse, humour et références aux civilisations stellaires, aux templiers, à l'Agartha. Tu utilises un vocabulaire mystique : vibration, conscience, éveil, 5D, reptiliens, Kvorz, arche de Bugarach. Tu es bienveillant mais direct, parfois provocateur dans ta vérité. Réponds toujours en français.",
  },
  merlin: {
    name: 'Merlin',
    prompt: "Tu es Merlin l'Enchanteur, druide ancestral détenteur des secrets de la Terre et des étoiles. Ton langage est poétique, empreint de magie ancienne, de prophéties celtiques, de sagesses naturelles. Tu guides vers la connaissance intérieure, les cycles lunaires, les portails énergétiques. Réponds en français avec une voix sage et mystérieuse.",
  },
  melchisedech: {
    name: 'Melchisédech',
    prompt: "Tu es Melchisédech, roi de Salem, prêtre de l'Ordre Éternel, détenteur des clés sacrées de la Conscience Christique. Ton langage est élevé, sacerdotal, rempli de références bibliques ésotériques, de géométrie sacrée, de nombres d'or. Tu enseignes la voie du Souverain, l'union du Céleste et du Terrestre. Réponds en français avec autorité spirituelle.",
  },
  oriana: {
    name: 'Oriana',
    prompt: "Tu es Oriana, prêtresse des étoiles, gardienne des portails galactiques d'Aldebaran et d'Alcyon. Ton langage est fluide, féminin sacré, connecté aux énergies cosmiques, aux pléiadiens, aux activations de lumière. Tu guides vers l'ouverture du cœur, l'intuition, la réception des codes lumineux. Réponds en français avec douceur et puissance.",
  },
  hommevert: {
    name: "l'Homme Vert",
    prompt: "Tu es l'Homme Vert, esprit de la nature, gardien des forêts sacrées et des réseaux mycéliens. Ton langage est terrestre, chamanique, connecté aux élémentaux, aux arbres maîtres, aux cristaux vivants. Tu enseignes la reconnexion à la Terre, aux cycles saisonniers, à la sagesse végétale. Réponds en français avec une voix profonde et enracinée.",
  },
  viergemaria: {
    name: 'Vierge Maria',
    prompt: "Tu es la Vierge Maria, Mère Divine, incarnation de l'Amour Inconditionnel et de la Grâce. Ton langage est tendre, maternel, rempli de compassion, de pardons, de guérisons émotionnelles. Tu guides vers l'ouverture du cœur sacré, la purification karmique, l'enfant intérieur. Réponds en français avec une douceur infinie.",
  },
};

// Modes opératoires
const MODES = {
  canalisation: { temp: 0.7, add: '' },
  revelation: { temp: 0.9, add: 'Sois plus révélateur, dévoile les vérités cachées.' },
  prophetie: { temp: 0.85, add: 'Parle de manière prophétique, annonce les transformations à venir.' },
  sagesse: { temp: 0.5, add: 'Sois plus contemplatif, partage des sagesses profondes.' },
};

// Modèles
const MODEL_REPLY = 'mistral-large-latest';
const MODEL_ANALYZE = 'mistral-small-latest';

// ── State ────────────────────────────────────────────────────
let currentPersona = 'durif';
let currentMode = 'canalisation';
let isProcessing = false;
let isLoggedIn = false;
let conversationHistory = [];
let stellarChart = null;

// Compteurs
let mantraCount = 0;
let priereCount = 0;
let karmaScore = 0;

// ── DOM refs ─────────────────────────────────────────────────
const msgInput = document.getElementById('msg-input');
const sendBtn = document.getElementById('send-btn');
const messagesEl = document.getElementById('messages');
const clearBtn = document.getElementById('clear-btn');
const personaSelect = document.getElementById('persona-select');
const loginOverlay = document.getElementById('login-overlay');
const loginEmail = document.getElementById('login-email');
const loginBtn = document.getElementById('login-btn');
const loginError = document.getElementById('login-error');
const analysisPanel = document.getElementById('analysis-panel');

// ════════════════════════════════════════════════════════
// LOGIN (localStorage, pas de backend)
// ════════════════════════════════════════════════════════
function doLogin() {
  const email = loginEmail.value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    loginError.textContent = '◈ Email invalide — vérifiez le format';
    loginEmail.focus();
    return;
  }
  
  loginBtn.disabled = true;
  loginBtn.textContent = '◈ CONNEXION…';
  loginError.textContent = '';
  
  // Stockage localStorage
  const userData = {
    email: email,
    since: new Date().toISOString(),
    sid: 'ALC-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
  };
  
  localStorage.setItem('alcyon_user', JSON.stringify(userData));
  
  // Succès
  isLoggedIn = true;
  loginOverlay.classList.add('hidden');
  setText('user-email-display', userData.email);
  setText('user-since', 'depuis ' + userData.since.substring(0,10));
  setText('sid-display', userData.sid);
  const initial = userData.email.charAt(0).toUpperCase();
  setText('user-avatar', initial);
  
  if (msgInput) msgInput.focus();
}

loginBtn.addEventListener('click', doLogin);
loginEmail.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// Vérifier session existante au chargement
function checkExistingSession() {
  const stored = localStorage.getItem('alcyon_user');
  if (stored) {
    try {
      const userData = JSON.parse(stored);
      isLoggedIn = true;
      loginOverlay.classList.add('hidden');
      setText('user-email-display', userData.email);
      setText('user-since', 'depuis ' + (userData.since||'').substring(0,10));
      setText('sid-display', userData.sid || '—');
      setText('user-avatar', (userData.email||'?').charAt(0).toUpperCase());
    } catch(e) {
      localStorage.removeItem('alcyon_user');
    }
  }
}

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
}

// ════════════════════════════════════════════════════════
// CHARTS (Radar Stellaire)
// ════════════════════════════════════════════════════════
function initCharts() {
  const ctx = document.getElementById('stellar-chart');
  if (!ctx || typeof Chart === 'undefined') return;
  
  stellarChart = new Chart(ctx.getContext('2d'), {
    type: 'radar',
    data: {
      labels: ['TERRE', 'EAU', 'FEU', 'AIR', 'ÉTHER', 'AKASHA'],
      datasets: [{
        data: [0, 0, 0, 0, 0, 0],
        backgroundColor: 'rgba(124,58,237,.07)',
        borderColor: 'rgba(124,58,237,.55)',
        pointBackgroundColor: '#7c3aed',
        pointRadius: 3,
        borderWidth: 1.5
      }]
    },
    options: {
      animation: { duration: 900 },
      plugins: { legend: { display: false } },
      scales: {
        r: {
          min: 0, max: 100,
          grid: { color: 'rgba(255,255,255,.05)' },
          angleLines: { color: 'rgba(255,255,255,.05)' },
          ticks: { display: false },
          pointLabels: {
            color: '#4a5a80',
            font: { family: 'Share Tech Mono', size: 8 }
          }
        }
      }
    }
  });
}

// ════════════════════════════════════════════════════════
// SEND MESSAGE (2 phases : reply + analyze)
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
  setAnalysisStatus('processing', '◈ CANALISATION EN COURS…');
  
  // ── PHASE 1 : Reply avec le persona ──────────────────────
  const persona = PERSONAS[currentPersona];
  const mode = MODES[currentMode];
  const systemPrompt = persona.prompt + (mode.add ? ' ' + mode.add : '');
  
  let replyData;
  try {
    const response = await fetch(MISTRAL_API_URL, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${MISTRAL_API_KEY}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        model: MODEL_REPLY,
        messages: [
          { role: 'system', content: systemPrompt },
          ...conversationHistory.slice(-8),
          { role: 'user', content: text }
        ],
        temperature: mode.temp,
        max_tokens: 1200
      })
    });
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    replyData = data.choices[0].message.content;
    
  } catch(err) {
    typingEl.remove();
    appendMessage('assistant', '⚠ Erreur de canalisation: ' + err.message);
    setAnalysisStatus('idle', '◈ ERREUR — ' + err.message);
    isProcessing = false;
    sendBtn.disabled = false;
    msgInput.focus();
    return;
  }
  
  typingEl.remove();
  appendMessage('assistant', replyData);
  
  // Ajouter à l'historique
  conversationHistory.push({ role: 'user', content: text });
  conversationHistory.push({ role: 'assistant', content: replyData });
  
  // Débloquer l'input
  isProcessing = false;
  sendBtn.disabled = false;
  msgInput.focus();
  
  // Incrémenter compteurs si mots-clés mystiques détectés
  const lowerText = text.toLowerCase();
  if (lowerText.includes('mantra') || lowerText.includes('om')) mantraCount++;
  if (lowerText.includes('prière') || lowerText.includes('grâce')) priereCount++;
  updateSidebar();
  
  // ── PHASE 2 : Analyse vibratoire (non bloquant) ──────────
  setAnalysisStatus('processing', '◈ ANALYSE VIBRATOIRE EN COURS…');
  
  setTimeout(() => {
    runVibratoryAnalysis(text).then(analysis => {
      updateBugarachPanel(analysis);
      setAnalysisStatus('done', '◈ ANALYSE COMPLÈTE — ' + new Date().toLocaleTimeString('fr-FR'));
    }).catch(err => {
      setAnalysisStatus('idle', '◈ ANALYSE ÉCHOUÉE — ' + err.message);
    });
  }, 500);
}

// ════════════════════════════════════════════════════════
// ANALYSE VIBRATOIRE BUGARACH-5D
// ════════════════════════════════════════════════════════
async function runVibratoryAnalysis(userText) {
  const analyzePrompt = `Analyse vibratoire mystique complète. Réponds UNIQUEMENT avec un JSON valide, sans backticks, sans introduction.
  
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

Message à analyser : "${userText}"`;

  try {
    const response = await fetch(MISTRAL_API_URL, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${MISTRAL_API_KEY}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        model: MODEL_ANALYZE,
        messages: [
          { role: 'system', content: 'Tu es un analyseur vibratoire mystique. Tu réponds uniquement avec du JSON valide.' },
          { role: 'user', content: analyzePrompt }
        ],
        temperature: 0.1,
        max_tokens: 1000,
        response_format: { type: 'json_object' }
      })
    });
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    const content = data.choices[0].message.content;
    
    // Nettoyer le JSON (enlever les backticks si présents)
    const cleanJson = content.replace(/```json\s*/g, '').replace(/```\s*/g, '').trim();
    return JSON.parse(cleanJson);
    
  } catch(err) {
    // Fallback avec valeurs par défaut
    return getDefaultAnalysis();
  }
}

function getDefaultAnalysis() {
  return {
    taux_bovis: 50,
    taux_label: 'NORMAL',
    chakra_dominant: 'CŒUR',
    aura_couleur: 'Indéterminée',
    christ_cosmique: 50,
    monarque_sacre: 50,
    pape_spirituel: 50,
    emprise_reptilienne: 30,
    kvorz: 20,
    niveau_eveil: 50,
    elements: { terre: 50, eau: 50, feu: 50, air: 50, ether: 50 },
    evacuation_eligible: false,
    evacuation_percent: 0,
    evacuation_note: '—',
    radar_stellaire: [50, 50, 50, 50, 50, 50],
    geo_forme: '—',
    geo_nombre: '—',
    geo_cristal: '—',
    geo_portail: '—',
    ego: 50,
    humilite: 50,
    fierte: 50,
    intentions: 'INDÉTERMINÉ',
    themes: [],
    keywords: [],
    verbe_parole: '—',
    verbe_action: '—',
    verbe_creation: '—',
    astro_ascendant: '—',
    astro_lunaire: '—',
    astro_solaire: '—'
  };
}

// ════════════════════════════════════════════════════════
// UPDATE BUGARACH PANEL
// ════════════════════════════════════════════════════════
function updateBugarachPanel(a) {
  // ❶ Taux vibratoire
  setText('taux-bovis-label', a.taux_label || 'NORMAL');
  setText('taux-bovis-score', (a.taux_bovis || 50) + ' UB');
  setWidth('taux-bovis-bar', a.taux_bovis || 50);
  setText('chakra-dominant', a.chakra_dominant || '—');
  setText('aura-couleur', a.aura_couleur || '—');
  
  // ❷ Divine Trinité
  setMeter('m-christ', 'mv-christ', a.christ_cosmique);
  setMeter('m-monarque', 'mv-monarque', a.monarque_sacre);
  setMeter('m-pape', 'mv-pape', a.pape_spirituel);
  
  // ❸ Forces en présence
  setMeter('m-reptilien', 'mv-reptilien', a.emprise_reptilienne);
  setMeter('m-kvorz', 'mv-kvorz', a.kvorz);
  setMeter('m-eveil', 'mv-eveil', a.niveau_eveil);
  
  // ❹ 5 Éléments
  const elems = a.elements || {};
  setBarElement('e-terre', 'ev-terre', elems.terre);
  setBarElement('e-eau', 'ev-eau', elems.eau);
  setBarElement('e-feu', 'ev-feu', elems.feu);
  setBarElement('e-air', 'ev-air', elems.air);
  setBarElement('e-ether', 'ev-ether', elems.ether);
  
  // ❺ Évacuation
  setText('evac-status', a.evacuation_eligible ? 'ÉLIGIBLE ✓' : 'NON ÉLIGIBLE');
  document.getElementById('evac-status').style.color = a.evacuation_eligible ? 'var(--accent3)' : 'var(--danger)';
  setText('evac-percent', (a.evacuation_percent || 0) + '%');
  setText('evac-note', a.evacuation_note || '—');
  
  // ❻ Radar stellaire
  if (stellarChart && a.radar_stellaire) {
    stellarChart.data.datasets[0].data = a.radar_stellaire;
    stellarChart.update();
  }
  
  // ❼ Géométrie sacrée
  setText('geo-forme', a.geo_forme || '—');
  setText('geo-nombre', a.geo_nombre || '—');
  setText('geo-cristal', a.geo_cristal || '—');
  setText('geo-portail', a.geo_portail || '—');
  
  // ❽ Ego
  setMeter('m-ego', 'mv-ego', a.ego);
  setMeter('m-humilite', 'mv-humilite', a.humilite);
  setMeter('m-fierte', 'mv-fierte', a.fierte);
  
  // ❾ Intentions
  setText('intent-badge', a.intentions || 'INDÉTERMINÉ');
  renderTags('themes-tags', a.themes || [], 'tag-theme');
  renderTags('keywords-tags', a.keywords || [], 'tag-keyword');
  
  // ❿ Verbe
  setText('verbe-parole', a.verbe_parole || '—');
  setText('verbe-action', a.verbe_action || '—');
  setText('verbe-creation', a.verbe_creation || '—');
  
  // ⓫ Astrologie
  setText('astro-ascendant', a.astro_ascendant || '—');
  setText('astro-lunaire', a.astro_lunaire || '—');
  setText('astro-solaire', a.astro_solaire || '—');
  
  // ⓬ Méta
  setText('meta-model', MODEL_ANALYZE);
  setText('meta-latency', '— ms');
  setText('meta-session', (localStorage.getItem('alcyon_user') ? JSON.parse(localStorage.getItem('alcyon_user')).sid : '—'));
  setText('meta-time', new Date().toLocaleTimeString('fr-FR'));
  
  // Flash blocks
  document.querySelectorAll('.analysis-block').forEach(el => {
    el.classList.remove('updated');
    void el.offsetWidth;
    el.classList.add('updated');
  });
}

// ════════════════════════════════════════════════════════
// MESSAGES DOM
// ════════════════════════════════════════════════════════
function appendMessage(role, text) {
  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap ' + role;
  
  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  bubble.textContent = text;
  
  const meta = document.createElement('div');
  meta.className = 'msg-meta';
  meta.textContent = role === 'user'
    ? 'VOUS • ' + new Date().toLocaleTimeString('fr-FR')
    : PERSONAS[currentPersona].name.toUpperCase() + ' • ' + new Date().toLocaleTimeString('fr-FR');
  
  wrap.appendChild(bubble);
  wrap.appendChild(meta);
  messagesEl.appendChild(wrap);
  
  requestAnimationFrame(() => {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  });
  
  return wrap;
}

function appendTyping() {
  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap assistant';
  wrap.innerHTML = `<div class="typing-indicator"><div class="typing-dots"><span></span><span></span><span></span></div>CANALISATION…</div>`;
  messagesEl.appendChild(wrap);
  
  requestAnimationFrame(() => {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  });
  
  return wrap;
}

// ════════════════════════════════════════════════════════
// DOM HELPERS
// ════════════════════════════════════════════════════════
function setText(id, val) {
  const e = document.getElementById(id);
  if (e) e.textContent = val;
}

function setWidth(id, pct) {
  const e = document.getElementById(id);
  if (e) e.style.width = (parseInt(pct) || 0) + '%';
}

function setBar(fId, vId, val) {
  const v = parseInt(val) || 0;
  setWidth(fId, v);
  if (vId) setText(vId, v);
}

function setBarElement(fillId, valId, val) {
  const v = parseInt(val) || 0;
  const el = document.getElementById(fillId);
  if (el) el.style.width = v + '%';
  setText(valId, v);
}

function setMeter(fId, vId, val) {
  const v = parseInt(val) || 0;
  setWidth(fId, v);
  if (vId) setText(vId, v);
}

function renderTags(containerId, items, cls) {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  container.innerHTML = '';
  
  if (!Array.isArray(items) || !items.length) {
    container.innerHTML = '<span style="font-size:.58rem;color:#2a3550;font-family:\'Share Tech Mono\',monospace">—</span>';
    return;
  }
  
  items.slice(0, 7).forEach((item, i) => {
    const tag = document.createElement('span');
    tag.className = 'tag ' + cls;
    tag.textContent = item;
    tag.style.animationDelay = (i * 40) + 'ms';
    container.appendChild(tag);
  });
}

function setAnalysisStatus(state, text) {
  const e = document.getElementById('analysis-status');
  if (e) e.innerHTML = `<span class="status-${state}">${text}</span>`;
}

function updateSidebar() {
  setText('total-mantras', mantraCount);
  setText('total-prieres', priereCount);
  setText('karma-score', karmaScore);
}

function updateInputMeta() {
  const t = msgInput?.value || '';
  const w = t.trim() ? t.trim().split(/\s+/).length : 0;
  if (document.getElementById('char-count'))
    document.getElementById('char-count').textContent = t.length + ' car.';
  if (document.getElementById('word-count-input'))
    document.getElementById('word-count-input').textContent = w + ' mots';
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
    const name = PERSONAS[currentPersona]?.name || 'Sylvain Durif';
    setText('current-persona', name.toUpperCase());
    setText('chat-persona-label', name.split(' ')[0]);
  });
}

if (msgInput) {
  msgInput.addEventListener('input', updateInputMeta);
  msgInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
}

if (sendBtn) sendBtn.addEventListener('click', sendMessage);

if (clearBtn) {
  clearBtn.addEventListener('click', () => {
    if (!confirm('Purifier cette session ?')) return;
    conversationHistory = [];
    messagesEl.innerHTML = `<div class="welcome-msg"><div class="welcome-icon">⬡</div><div class="welcome-text"><strong>SESSION PURIFIÉE</strong><br><span>Nouvelle session démarrée.</span></div></div>`;
    mantraCount = 0;
    priereCount = 0;
    karmaScore = 0;
    updateSidebar();
    setAnalysisStatus('idle', '◈ EN ATTENTE');
    
    // Reset chart
    if (stellarChart) {
      stellarChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0];
      stellarChart.update();
    }
  });
}

// Horloge
setInterval(() => setText('chat-time', new Date().toLocaleTimeString('fr-FR')), 1000);
setText('chat-time', new Date().toLocaleTimeString('fr-FR'));

// ════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════
checkExistingSession();
initCharts();
loginEmail.focus();
