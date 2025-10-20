(() => {
  const OVERLAY_ID = 'app-confirm-overlay';
  let overlay;
  let dialog;
  let titleEl;
  let messageEl;
  let confirmBtn;
  let cancelBtn;
  let closeBtn;
  let resolveFn;

  function ensureElements() {
    if (overlay) {
      return;
    }

    overlay = document.createElement('div');
    overlay.id = OVERLAY_ID;
    overlay.className = 'app-confirm-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = `
      <div class="app-confirm-dialog" role="document">
        <button class="close-btn-plain" type="button" aria-label="Close dialog">&times;</button>
        <h3 class="app-confirm-title">Confirm action</h3>
        <p class="app-confirm-message"></p>
        <div class="app-confirm-actions">
          <button type="button" class="app-confirm-btn cancel">Cancel</button>
          <button type="button" class="app-confirm-btn confirm">Confirm</button>
        </div>
      </div>
    `;

    dialog = overlay.querySelector('.app-confirm-dialog');
    titleEl = overlay.querySelector('.app-confirm-title');
    messageEl = overlay.querySelector('.app-confirm-message');
    confirmBtn = overlay.querySelector('.app-confirm-btn.confirm');
    cancelBtn = overlay.querySelector('.app-confirm-btn.cancel');
    closeBtn = overlay.querySelector('.close-btn-plain');

    confirmBtn.addEventListener('click', () => finish(true));
    cancelBtn.addEventListener('click', () => finish(false));
    closeBtn.addEventListener('click', () => finish(false));
    overlay.addEventListener('click', event => {
      if (event.target === overlay) finish(false);
    });

    document.addEventListener('keydown', event => {
      if (!overlay.classList.contains('show')) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        finish(false);
      }
      if (event.key === 'Enter' && document.activeElement === cancelBtn) {
        event.preventDefault();
        finish(false);
      }
    });

    document.body.appendChild(overlay);
  }

  function finish(result) {
    if (!overlay.classList.contains('show')) return;
    overlay.classList.remove('show');
    document.body.classList.remove('body-lock-scroll');
    setTimeout(() => {
      if (resolveFn) resolveFn(result);
      resolveFn = null;
    }, 180);
  }

  function setOptions(options) {
    const { title, message, confirmText, cancelText, tone } = {
      title: 'Confirm action',
      message: '',
      confirmText: 'Confirm',
      cancelText: 'Cancel',
      tone: 'danger',
      ...options,
    };

    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    confirmBtn.classList.toggle('neutral', tone !== 'danger');
  }

  function confirmDialog(options) {
    ensureElements();

    return new Promise(resolve => {
      resolveFn = resolve;
      setOptions(options || {});
      requestAnimationFrame(() => {
        document.body.classList.add('body-lock-scroll');
        overlay.classList.add('show');
        confirmBtn.focus({ preventScroll: true });
      });
    });
  }

  function bindDataConfirm() {
    document.addEventListener('click', async event => {
      const trigger = event.target.closest('[data-confirm]');
      if (!trigger) return;

      const form = trigger.closest('form');
      if (!form) return;

      event.preventDefault();
      const message = trigger.getAttribute('data-confirm') || 'Are you sure?';
      const title = trigger.getAttribute('data-confirm-title') || 'Please confirm';
      const confirmText = trigger.getAttribute('data-confirm-accept') || trigger.textContent.trim() || 'Confirm';
      const tone = trigger.getAttribute('data-confirm-tone') || 'danger';

      const confirmed = typeof window.dialogs?.confirm === 'function'
        ? await window.dialogs.confirm({ title, message, confirmText, cancelText: 'Cancel', tone })
        : window.confirm(message);

      if (confirmed) form.submit();
    });
  }

  window.dialogs = window.dialogs || {};
  window.dialogs.confirm = options => {
    try {
      return confirmDialog(options);
    } catch (error) {
      console.warn('Falling back to native confirm dialog:', error);
      return Promise.resolve(window.confirm(options?.message || 'Are you sure?'));
    }
  };

  bindDataConfirm();
})();
