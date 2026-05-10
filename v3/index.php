<?php require_once 'config.php'; require_once 'database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0a0f1a">
<title>AETHER/ALCYON v5.0 • PORTAIL ULTIME</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?1">
<style>
/* Styles spécifiques V5 - Fusion Cyberpunk + Spirituel */
:root {
  --neon-cyan: #00e5ff;
  --neon-purple: #7c3aed;
  --neon-green: #10b981;
  --neon-pink: #ec4899;
  --bg-dark: #0a0f1a;
  --bg-panel: #111827;
  --text-primary: #f3f4f6;
  --text-secondary: #9ca3af;
}

body {
  background: var(--bg-dark);
  color: var(--text-primary);
  font-family: 'Share Tech Mono', monospace;
  overflow: hidden;
}

.scanlines {
  position: fixed;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    rgba(0, 0, 0, 0.15),
    rgba(0, 0, 0, 0.15) 1px,
    transparent 1px,
    transparent 2px
  );
  pointer-events: none;
  z-index: 9999;
}

.grid-overlay {
  position: fixed;
  inset: 0;
  background-image: 
    linear-gradient(rgba(0, 229, 255, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 229, 255, 0.03) 1px, transparent 1px);
  background-size: 50px 50px;
  pointer-events: none;
  z-index: 9998;
  animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
  0% { transform: translate(0, 0); }
  100% { transform: translate(50px, 50px); }
}

.login-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 15, 26, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.login-card {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(31, 41, 55, 0.9));
  border: 1px solid var(--neon-cyan);
  border-radius: 12px;
  padding: 3rem;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 0 40px rgba(0, 229, 255, 0.2);
  text-align: center;
}

.login-logo {
  font-size: 4rem;
  margin-bottom: 1rem;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.05); }
}

.login-title {
  font-family: 'Orbitron', sans-serif;
  font-size: 2rem;
  font-weight: 900;
  background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

.login-sub {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-bottom: 2rem;
}

.login-label {
  display: block;
  text-align: left;
  font-size: 0.75rem;
  color: var(--neon-cyan);
  margin-bottom: 0.5rem;
  letter-spacing: 1px;
}

.login-input {
  width: 100%;
  padding: 0.75rem 1rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(0, 229, 255, 0.3);
  border-radius: 6px;
  color: var(--text-primary);
  font-family: 'Share Tech Mono', monospace;
  font-size: 1rem;
  margin-bottom: 1.5rem;
  transition: all 0.3s;
}

.login-input:focus {
  outline: none;
  border-color: var(--neon-cyan);
  box-shadow: 0 0 20px rgba(0, 229, 255, 0.3);
}

.login-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
  border: none;
  border-radius: 6px;
  color: white;
  font-family: 'Orbitron', sans-serif;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 229, 255, 0.4);
}

.login-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.login-error {
  color: #ef4444;
  font-size: 0.85rem;
  margin-top: 1rem;
  min-height: 1.2em;
}

.login-hint {
  color: var(--text-secondary);
  font-size: 0.75rem;
  margin-top: 1.5rem;
  line-height: 1.5;
}

.app-shell {
  display: flex;
  height: 100vh;
  opacity: 0;
  transition: opacity 0.5s;
}

.app-shell.active {
  opacity: 1;
}

.sidebar {
  width: 280px;
  background: var(--bg-panel);
  border-right: 1px solid rgba(0, 229, 255, 0.2);
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.brand-block {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 229, 255, 0.2);
}

.brand-logo {
  font-size: 2.5rem;
  animation: rotate 10s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.brand-name {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.5rem;
  font-weight: 900;
  background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-ver {
  display: block;
  font-size: 0.65rem;
  color: var(--text-secondary);
  letter-spacing: 1px;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(0, 229, 255, 0.05);
  border-radius: 8px;
  border: 1px solid rgba(0, 229, 255, 0.1);
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.2rem;
}

.user-email {
  font-size: 0.85rem;
  color: var(--text-primary);
}

.user-since {
  font-size: 0.7rem;
  color: var(--text-secondary);
}

.status-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--neon-green);
  padding: 0.5rem;
  background: rgba(16, 185, 129, 0.1);
  border-radius: 6px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  animation: blink 1.5s infinite;
}

.dot-green { background: var(--neon-green); }
.dot-cyan { background: var(--neon-cyan); }
.dot-purple { background: var(--neon-purple); }

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.session-id {
  margin-left: auto;
  color: var(--text-secondary);
  font-size: 0.65rem;
}

.side-nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: 6px;
  transition: all 0.3s;
}

.nav-item:hover {
  background: rgba(0, 229, 255, 0.1);
  color: var(--text-primary);
}

.nav-item.active {
  background: linear-gradient(135deg, rgba(0, 229, 255, 0.2), rgba(124, 58, 237, 0.2));
  color: var(--neon-cyan);
  border: 1px solid rgba(0, 229, 255, 0.3);
}

.nav-icon {
  font-size: 1.2rem;
}

.section-label {
  font-size: 0.7rem;
  color: var(--text-secondary);
  letter-spacing: 1px;
  margin-bottom: 0.75rem;
}

.mode-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.mode-btn {
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(0, 229, 255, 0.2);
  border-radius: 4px;
  color: var(--text-secondary);
  font-family: 'Share Tech Mono', monospace;
  font-size: 0.65rem;
  cursor: pointer;
  transition: all 0.3s;
}

.mode-btn:hover {
  border-color: var(--neon-cyan);
  color: var(--text-primary);
}

.mode-btn.active {
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
  color: white;
  border-color: transparent;
}

.cyber-select {
  width: 100%;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(0, 229, 255, 0.2);
  border-radius: 4px;
  color: var(--text-primary);
  font-family: 'Share Tech Mono', monospace;
  font-size: 0.75rem;
  cursor: pointer;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.stat-item {
  text-align: center;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.stat-val {
  display: block;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--neon-cyan);
}

.stat-lbl {
  font-size: 0.6rem;
  color: var(--text-secondary);
}

.clear-btn {
  margin-top: auto;
  padding: 0.75rem;
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 6px;
  color: #ef4444;
  font-family: 'Share Tech Mono', monospace;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.3s;
}

.clear-btn:hover {
  background: rgba(239, 68, 68, 0.3);
}

.chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: rgba(0, 0, 0, 0.2);
}

.section-panel {
  display: none;
  flex: 1;
  overflow: hidden;
}

.section-panel.active {
  display: flex;
  flex-direction: column;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(0, 229, 255, 0.2);
  background: var(--bg-panel);
}

.chat-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Orbitron', sans-serif;
  font-weight: 700;
  color: var(--neon-cyan);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: var(--neon-green);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.chat-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.welcome-msg {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  background: rgba(0, 229, 255, 0.05);
  border: 1px solid rgba(0, 229, 255, 0.2);
  border-radius: 12px;
  align-items: flex-start;
}

.welcome-icon {
  font-size: 2rem;
  animation: pulse 2s infinite;
}

.welcome-text {
  line-height: 1.6;
}

.welcome-text strong {
  display: block;
  color: var(--neon-cyan);
  margin-bottom: 0.5rem;
}

.input-zone {
  padding: 1.5rem;
  background: var(--bg-panel);
  border-top: 1px solid rgba(0, 229, 255, 0.2);
}

.input-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.7rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

.input-row {
  display: flex;
  gap: 0.75rem;
}

#msg-input {
  flex: 1;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(0, 229, 255, 0.2);
  border-radius: 8px;
  color: var(--text-primary);
  font-family: 'Share Tech Mono', monospace;
  font-size: 0.95rem;
  resize: none;
  transition: all 0.3s;
}

#msg-input:focus {
  outline: none;
  border-color: var(--neon-cyan);
  box-shadow: 0 0 20px rgba(0, 229, 255, 0.2);
}

#send-btn {
  padding: 0 1.5rem;
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  transition: all 0.3s;
}

#send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 0 30px rgba(0, 229, 255, 0.4);
}

#send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.analysis-panel {
  width: 380px;
  background: var(--bg-panel);
  border-left: 1px solid rgba(0, 229, 255, 0.2);
  overflow-y: auto;
  padding: 1rem;
}

.panel-header {
  text-align: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 229, 255, 0.2);
  margin-bottom: 1rem;
}

.panel-title {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.2rem;
  font-weight: 900;
  color: var(--neon-purple);
}

.panel-ver {
  color: var(--neon-cyan);
}

.panel-sub {
  font-size: 0.7rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.analysis-status {
  margin-top: 0.75rem;
  font-size: 0.75rem;
}

.status-idle { color: var(--text-secondary); }
.status-processing { color: var(--neon-cyan); }
.status-complete { color: var(--neon-green); }
.status-error { color: #ef4444; }

.analysis-block {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(124, 58, 237, 0.2);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: all 0.3s;
}

.analysis-block.updated {
  border-color: var(--neon-purple);
  box-shadow: 0 0 20px rgba(124, 58, 237, 0.3);
}

.block-title {
  font-size: 0.75rem;
  color: var(--neon-purple);
  margin-bottom: 0.75rem;
  letter-spacing: 1px;
}

.sentiment-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.sentiment-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--neon-cyan);
}

.sentiment-score {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.sentiment-track {
  height: 6px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.sentiment-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
  border-radius: 3px;
  transition: width 0.5s ease;
}

.emotion-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.emotion-item {
  text-align: center;
}

.emo-label {
  display: block;
  font-size: 0.6rem;
  color: var(--text-secondary);
}

.emo-val {
  display: block;
  font-size: 0.75rem;
  color: var(--text-primary);
  margin-top: 0.25rem;
}

.field-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  margin-top: 0.5rem;
}

.field-label {
  color: var(--text-secondary);
}

.field-val {
  color: var(--text-primary);
}

.field-val.accent {
  color: var(--neon-cyan);
}

.tags-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.tag {
  padding: 0.25rem 0.5rem;
  background: rgba(0, 229, 255, 0.1);
  border: 1px solid rgba(0, 229, 255, 0.2);
  border-radius: 4px;
  font-size: 0.65rem;
  color: var(--neon-cyan);
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.mt-half { margin-top: 0.5rem; }

.style-meters, .psych-meters, .mkt-meters {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.style-meter-row, .meter-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.65rem;
}

.style-meter-row span:first-child, .meter-row span:first-child {
  width: 70px;
  color: var(--text-secondary);
}

.style-track, .meter-track {
  flex: 1;
  height: 4px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 2px;
  overflow: hidden;
}

.style-fill, .meter-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.5s ease;
}

.style-fill.accent { background: var(--neon-cyan); }
.style-fill.purple { background: var(--neon-purple); }
.style-fill.green { background: var(--neon-green); }
.meter-fill.danger { background: #ef4444; }
.meter-fill.warn { background: #f59e0b; }
.meter-fill.accent { background: var(--neon-cyan); }
.meter-fill.purple { background: var(--neon-purple); }
.meter-fill.green { background: var(--neon-green); }

.style-meter-row span:last-child, .meter-row span:last-child {
  width: 25px;
  text-align: right;
  color: var(--text-primary);
  font-size: 0.6rem;
}

.psych-grid, .socio-grid, .struct-grid6 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.pg-item, .sg-item {
  text-align: center;
}

.pg-label, .sg-label {
  display: block;
  font-size: 0.55rem;
  color: var(--text-secondary);
}

.pg-val, .sg-val {
  display: block;
  font-size: 0.7rem;
  color: var(--text-primary);
  margin-top: 0.25rem;
}

.big5-grid {
  display: flex;
  justify-content: space-around;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.big5-bar-wrap {
  text-align: center;
}

.big5-bar-outer {
  width: 30px;
  height: 80px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  overflow: hidden;
  margin: 0 auto 0.25rem;
}

.big5-bar-fill {
  width: 100%;
  background: linear-gradient(to top, var(--neon-cyan), var(--neon-purple));
  transition: height 0.5s ease;
}

.big5-bar-val {
  font-size: 0.65rem;
  color: var(--text-primary);
}

.big5-bar-label {
  font-size: 0.5rem;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.mkt-persona {
  text-align: center;
  padding: 0.5rem;
  background: rgba(124, 58, 237, 0.1);
  border: 1px solid rgba(124, 58, 237, 0.2);
  border-radius: 6px;
  font-size: 0.75rem;
  color: var(--neon-purple);
  margin-bottom: 0.75rem;
}

.mkt-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  margin-top: 0.5rem;
}

.charts-section {
  padding: 0.75rem;
}

.hidden { display: none !important; }

/* Message bubbles */
.message {
  display: flex;
  gap: 0.75rem;
  max-width: 85%;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message.user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.message.user .message-avatar {
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
}

.message.assistant .message-avatar {
  background: linear-gradient(135deg, var(--neon-purple), var(--neon-pink));
}

.message-content {
  padding: 0.75rem 1rem;
  border-radius: 12px;
  line-height: 1.5;
  font-size: 0.9rem;
}

.message.user .message-content {
  background: linear-gradient(135deg, rgba(0, 229, 255, 0.2), rgba(124, 58, 237, 0.2));
  border: 1px solid rgba(0, 229, 255, 0.3);
}

.message.assistant .message-content {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(124, 58, 237, 0.2);
}

.typing-indicator {
  display: flex;
  gap: 0.25rem;
  padding: 0.75rem 1rem;
}

.typing-dot {
  width: 6px;
  height: 6px;
  background: var(--neon-cyan);
  border-radius: 50%;
  animation: typingBounce 1.4s infinite;
}

.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-10px); }
}

.section-idle {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  color: var(--text-secondary);
}

.section-idle-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.section-idle-title {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.2rem;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.section-idle-sub {
  font-size: 0.85rem;
  line-height: 1.6;
  max-width: 400px;
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.2);
}

::-webkit-scrollbar-thumb {
  background: rgba(0, 229, 255, 0.3);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 229, 255, 0.5);
}
</style>
</head>
<body>

<div class="scanlines"></div>
<div class="grid-overlay"></div>

<!-- ═══ LOGIN MODAL ══════════════════════════════════════════ -->
<div class="login-overlay" id="login-overlay">
  <div class="login-card">
    <div class="login-logo">𓀀</div>
    <div class="login-title">AETHER/ALCYON v5.0</div>
    <div class="login-sub">◈ PORTAIL ULTIME — FUSION CYBERPUNK & SPIRITUELLE</div>
    <label class="login-label" for="login-email">◤ EMAIL VIBRATOIRE</label>
    <input type="email" id="login-email" class="login-input" placeholder="votre@email.com" autocomplete="email">
    <button class="login-btn" id="login-btn">⟶ OUVRIR LE PORTAIL</button>
    <div class="login-error" id="login-error"></div>
    <div class="login-hint">Votre email crée ou reprend votre profil. Aucun mot de passe requis. Vos canalisation et analyses sont mémorisées.</div>
  </div>
</div>

<!-- ═══ APP SHELL ═════════════════════════════════════════════ -->
<div class="app-shell" id="app-shell">

  <!-- SIDEBAR ──────────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">

    <div class="brand-block">
      <div class="brand-logo">𓀀</div>
      <div class="brand-text">
        <span class="brand-name">AETHER/ALCYON</span>
        <span class="brand-ver">v5.0 • ULTIME</span>
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
          CANAL NEURAL AETHER/ALCYON
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
            <strong>AETHER/ALCYON v5.0 — PORTAIL ULTIME ACTIF</strong><br>
            <span>Fusion du Panopticon (V1) et de Bugarach 5D (V2). Chaque message est analysé par 3 moteurs IA : réponse personnalisée (K1), analyse psycho-émotionnelle (K2), radiographie vibratoire 5D (K3). Sylvain Durif et autres entités spirituelles disponibles.</span>
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
          <textarea id="msg-input" placeholder="Parlez à l'entité… [ENTER envoyer, SHIFT+ENTER saut]" rows="2"></textarea>
          <button id="send-btn" type="button"><span>⟶</span></button>
        </div>
      </div>
    </div>

    <!-- SECTION ANALYSE -->
    <div id="section-analysis" class="section-panel">
      <div id="cognitive-content">
        <div class="section-idle">
          <div class="section-idle-icon">◉</div>
          <div class="section-idle-title">ANALYSE 5D</div>
          <div class="section-idle-sub">Radiographie complète : psycho-émotionnelle + vibratoire.<br>Démarrez une conversation pour activer les analyses.</div>
        </div>
      </div>
    </div>

    <!-- SECTION HISTORIQUE -->
    <div id="section-history" class="section-panel">
      <div id="history-content">
        <div class="section-idle">
          <div class="section-idle-icon">◎</div>
          <div class="section-idle-title">ARCHIVES</div>
          <div class="section-idle-sub">Vos échanges seront affichés ici.<br>Chargement automatique à l'ouverture.</div>
        </div>
      </div>
    </div>

    <!-- SECTION SYSTÈME -->
    <div id="section-system" class="section-panel">
      <div id="system-content">
        <div class="section-idle">
          <div class="section-idle-icon">⬟</div>
          <div class="section-idle-title">DIAGNOSTICS</div>
          <div class="section-idle-sub">Statut des clés API, base de données, PHP.<br>Chargement automatique à l'ouverture.</div>
        </div>
      </div>
    </div>

  </main>

  <!-- ANALYSIS PANEL ──────────────────────────────────────── -->
  <aside class="analysis-panel" id="analysis-panel">

    <div class="panel-header">
      <div class="panel-title">ANALYSE<span class="panel-ver">-5D</span></div>
      <div class="panel-sub">RADIOGRAPHIE PSYCHO-VIBRATOIRE</div>
      <div class="analysis-status" id="analysis-status">
        <span class="status-idle">◈ EN ATTENTE</span>
      </div>
    </div>

    <!-- ❶ VECTEUR ÉMOTIONNEL -->
    <div class="analysis-block" id="block-sentiment">
      <div class="block-title">❶ VECTEUR ÉMOTIONNEL</div>
      <div class="sentiment-row">
        <span class="sentiment-label" id="sentiment-label">NEUTRE</span>
        <span class="sentiment-score" id="sentiment-score">50/100</span>
      </div>
      <div class="sentiment-track"><div class="sentiment-bar" id="sentiment-bar" style="width:50%"></div></div>
      <div class="emotion-grid">
        <div class="emotion-item"><span class="emo-label">PRIMAIRE</span><span class="emo-val" id="emotion-primary">—</span></div>
        <div class="emotion-item"><span class="emo-label">SECONDAIRE</span><span class="emo-val" id="emotion-secondary">—</span></div>
      </div>
      <div class="field-row" style="margin-top:.3rem"><span class="field-label">TON</span><span class="field-val accent" id="tone-val">—</span></div>
    </div>

    <!-- ❷ TAUX VIBRATOIRE -->
    <div class="analysis-block">
      <div class="block-title">❷ TAUX VIBRATOIRE BOVIS</div>
      <div class="sentiment-row">
        <span class="sentiment-label" id="vibro-label">65 U.B.</span>
        <span class="sentiment-score" id="vibro-score">65/100</span>
      </div>
      <div class="sentiment-track"><div class="sentiment-bar" id="vibro-bar" style="width:65%"></div></div>
      <div class="field-row"><span class="field-label">AURA</span><span class="field-val" id="aura-val">—</span></div>
    </div>

    <!-- ❸ CHAKRAS -->
    <div class="analysis-block">
      <div class="block-title">❸ ALIGNEMENT DES CHAKRAS</div>
      <div class="psych-meters">
        <div class="meter-row"><span>RACINE</span><div class="meter-track"><div class="meter-fill green" id="m-chakra-racine"></div></div><span id="mv-chakra-racine">—</span></div>
        <div class="meter-row"><span>SACRÉ</span><div class="meter-track"><div class="meter-fill warn" id="m-chakra-sacre"></div></div><span id="mv-chakra-sacre">—</span></div>
        <div class="meter-row"><span>PLEXUS</span><div class="meter-track"><div class="meter-fill danger" id="m-chakra-plexus"></div></div><span id="mv-chakra-plexus">—</span></div>
        <div class="meter-row"><span>CŒUR</span><div class="meter-track"><div class="meter-fill accent" id="m-chakra-coeur"></div></div><span id="mv-chakra-coeur">—</span></div>
        <div class="meter-row"><span>GORGE</span><div class="meter-track"><div class="meter-fill purple" id="m-chakra-gorge"></div></div><span id="mv-chakra-gorge">—</span></div>
        <div class="meter-row"><span>3ᵉ ŒIL</span><div class="meter-track"><div class="meter-fill green" id="m-chakra-troisieme-oeil"></div></div><span id="mv-chakra-troisieme-oeil">—</span></div>
        <div class="meter-row"><span>COURONNE</span><div class="meter-track"><div class="meter-fill purple" id="m-chakra-couronne"></div></div><span id="mv-chakra-couronne">—</span></div>
      </div>
    </div>

    <!-- ❹ TRINITÉ DIVINE -->
    <div class="analysis-block">
      <div class="block-title">❹ TRINITÉ DIVINE</div>
      <div class="psych-meters">
        <div class="meter-row"><span>CHRISTIQUE</span><div class="meter-track"><div class="meter-fill accent" id="m-trinite-christique"></div></div><span id="mv-trinite-christique">—</span></div>
        <div class="meter-row"><span>MONARCHIQUE</span><div class="meter-track"><div class="meter-fill purple" id="m-trinite-monarchique"></div></div><span id="mv-trinite-monarchique">—</span></div>
        <div class="meter-row"><span>PAPAL</span><div class="meter-track"><div class="meter-fill warn" id="m-trinite-papal"></div></div><span id="mv-trinite-papal">—</span></div>
      </div>
    </div>

    <!-- ❺ EMPRISE / ÉVEIL -->
    <div class="analysis-block">
      <div class="block-title">❺ FORCES SPIRITUELLES</div>
      <div class="psych-meters">
        <div class="meter-row"><span>EMPRISE REPTILIENNE</span><div class="meter-track"><div class="meter-fill danger" id="m-emprise-reptilienne"></div></div><span id="mv-emprise-reptilienne">—</span></div>
        <div class="meter-row"><span>KVORZ LEVEL</span><div class="meter-track"><div class="meter-fill warn" id="m-kvorz-level"></div></div><span id="mv-kvorz-level">—</span></div>
        <div class="meter-row"><span>ÉVEIL CONSCIENCE</span><div class="meter-track"><div class="meter-fill green" id="m-eveil-conscience"></div></div><span id="mv-eveil-conscience">—</span></div>
      </div>
    </div>

    <!-- ❻ RADAR STELLAIRE -->
    <div class="analysis-block charts-section">
      <div class="block-title">❻ RADAR STELLAIRE</div>
      <canvas id="radar-chart" height="200"></canvas>
    </div>

    <!-- ❼ GÉOMÉTRIE SACRÉE -->
    <div class="analysis-block">
      <div class="block-title">❼ GÉOMÉTRIE SACRÉE</div>
      <div class="psych-grid">
        <div class="pg-item"><span class="pg-label">METATRON</span><span class="pg-val" id="geo-metatron">—</span></div>
        <div class="pg-item"><span class="pg-label">FLEUR DE VIE</span><span class="pg-val" id="geo-flower-of-life">—</span></div>
        <div class="pg-item"><span class="pg-label">GRAINE DE VIE</span><span class="pg-val" id="geo-seed-of-life">—</span></div>
        <div class="pg-item"><span class="pg-label">MERKABA</span><span class="pg-val" id="geo-merkaba">—</span></div>
      </div>
    </div>

    <!-- ❽ EGO DISSOLUTION -->
    <div class="analysis-block">
      <div class="block-title">❽ DISSOLUTION DE L'EGO</div>
      <div class="sentiment-track" style="margin-bottom:.5rem"><div class="sentiment-bar" id="ego-bar" style="width:0%"></div></div>
      <div class="sentiment-row"><span class="sentiment-label">SCORE</span><span class="sentiment-score" id="ego-score">0/100</span></div>
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
      <div class="socio-grid">
        <div class="sg-item"><span class="sg-label">LUNAIRE</span><span class="sg-val" id="astro-lunaire">—</span></div>
        <div class="sg-item"><span class="sg-label">SOLAIRE</span><span class="sg-val" id="astro-solaire">—</span></div>
        <div class="sg-item"><span class="sg-label">ASCENDANT</span><span class="sg-val" id="astro-ascendant">—</span></div>
        <div class="sg-item"><span class="sg-label">MAÎTRE NATAL</span><span class="sg-val" id="astro-maitre-natal">—</span></div>
      </div>
    </div>

    <!-- ⓬ META -->
    <div class="analysis-block">
      <div class="block-title">⓬ MÉTA-DONNÉES</div>
      <div class="field-row"><span class="field-label">PERSONA</span><span class="field-val accent" id="meta-persona">—</span></div>
      <div class="field-row"><span class="field-label">LATENCE</span><span class="field-val" id="meta-latency">—</span></div>
      <div class="field-row"><span class="field-label">TOKENS IN</span><span class="field-val" id="meta-tin">—</span></div>
      <div class="field-row"><span class="field-label">TOKENS OUT</span><span class="field-val" id="meta-tout">—</span></div>
      <div class="field-row"><span class="field-label">SESSION</span><span class="field-val" id="meta-session">—</span></div>
      <div class="field-row"><span class="field-label">TEMPS</span><span class="field-val" id="meta-time">—</span></div>
    </div>

  </aside>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="script.js"></script>
</body>
</html>
