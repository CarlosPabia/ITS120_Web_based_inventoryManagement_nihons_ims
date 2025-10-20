(() => {
  const TYPES = {
    success: { accent: '#2e7d32', icon: '\u2713' },
    error: { accent: '#c0392b', icon: '\u2715' },
    warn: { accent: '#e6b300', icon: '!' },
    info: { accent: '#7b4a38', icon: '\u2139' },
  };

  function ensureContainer() {
    let el = document.getElementById('toast-stack');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast-stack';
      el.setAttribute('aria-live', 'polite');
      el.setAttribute('aria-atomic', 'true');
      document.body.appendChild(el);
    }
    return el;
  }

  function injectStyles() {
    if (document.getElementById('toast-styles')) return;
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
      #toast-stack { position: fixed; right: 16px; top: 76px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
      @media (max-width: 600px) { #toast-stack { left: 50%; right: auto; transform: translateX(-50%); top: auto; bottom: 16px; width: min(92vw, 460px); } }
      .toast-card { background: #fff; color: #2f2f2f; border-radius: 12px; box-shadow: 0 12px 28px rgba(17,26,38,.18); border: 1px solid rgba(0,0,0,.06); overflow: hidden; min-width: 280px; max-width: 420px; display: grid; grid-template-columns: 12px 1fr auto; align-items: stretch; opacity: 0; transform: translateY(-6px); transition: opacity .18s ease, transform .18s ease; }
      .toast-card.show { opacity: 1; transform: translateY(0); }
      .toast-accent { width: 12px; }
      .toast-body { padding: 12px 14px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
      .toast-icon { font-weight: 700; opacity: .8; }
      .toast-close { appearance: none; border: none; background: transparent; color: #6a6f74; padding: 10px; font-size: 16px; cursor: pointer; }
      .toast-close:hover { color: #2f2f2f; }
    `;
    document.head.appendChild(style);
  }

  function toast(type, message, opts = {}) {
    injectStyles();
    const t = TYPES[type] || TYPES.info;
    const container = ensureContainer();

    const card = document.createElement('div');
    card.className = 'toast-card';
    card.role = 'status';
    card.innerHTML = `
      <div class="toast-accent" style="background:${t.accent}"></div>
      <div class="toast-body"><span class="toast-icon">${t.icon}</span><span>${message}</span></div>
      <button class="toast-close" aria-label="Dismiss">×</button>
    `;

    const closer = card.querySelector('.toast-close');
    const remove = () => { card.classList.remove('show'); setTimeout(() => card.remove(), 180); };
    closer.addEventListener('click', remove);

    container.appendChild(card);
    requestAnimationFrame(() => card.classList.add('show'));

    const duration = opts.duration ?? (type === 'error' ? 5000 : type === 'warn' ? 4000 : 3000);
    if (duration > 0) setTimeout(remove, duration);
  }

  window.notify = {
    success: (m, o) => toast('success', m, o),
    error: (m, o) => toast('error', m, o),
    warn: (m, o) => toast('warn', m, o),
    info: (m, o) => toast('info', m, o),
    toast,
  };
})();