/**
 * Nafas Chatbot Pro — chatbot.js
 * Vanilla JS, no dependencies, IE11+ is NOT supported intentionally.
 * All DOM creation is XSS-safe (textContent / setAttribute with sanitization).
 */
(function () {
  'use strict';

  /* ──────────────────────────────────────────────────────────
     Utilities
  ────────────────────────────────────────────────────────── */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const el = (tag, cls, attrs = {}) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    Object.entries(attrs).forEach(([k, v]) => {
      if (k === 'html') { e.innerHTML = v; }
      else if (k.startsWith('on')) { e.addEventListener(k.slice(2), v); }
      else { e.setAttribute(k, v); }
    });
    return e;
  };

  // Safe text setter
  const setText = (el, str) => { el.textContent = String(str ?? ''); };

  // Basic Markdown → safe HTML (no innerHTML from user content except this parser)
  function md2html(raw) {
    let s = String(raw || '');
    // Escape HTML
 s = s.replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')   // ✅  Correct — escape < as &lt;
         .replace(/>/g, '&gt;')   // ✅  Also escape > as &gt; for completeness
         .replace(/"/g, '&quot;');

		 // Code blocks
    s = s.replace(/```([\s\S]*?)```/g, (_, c) => `<pre><code>${c.trim()}</code></pre>`);
    // Inline code
    s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
    // Bold / italic
    s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    // Links
// ✅  Complete regex: match a URL, wrap it in an anchor tag
s = s.replace(
    /(https?:\/\/[^\s<>"]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
);    // Lists
    const lines = s.split('\n');
    const out = []; let inList = false;
    lines.forEach(line => {
      const m = line.match(/^\s*[-*]\s+(.+)$/);
      if (m) {
        if (!inList) { out.push('<ul>'); inList = true; }
        out.push(`<li>${m[1]}</li>`);
      } else {
        if (inList) { out.push('</ul>'); inList = false; }
        if (line.trim()) out.push(`<p>${line}</p>`);
      }
    });
    if (inList) out.push('</ul>');
    return out.join('');
  }

  function uid() {
    return 'ncp_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
  }

  /* LocalStorage wrappers (gracefully degrade) */
  const ls = {
    get: k => { try { return localStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
    del: k => { try { localStorage.removeItem(k); } catch {} },
  };

  /* ──────────────────────────────────────────────────────────
     Global config injected by PHP
  ────────────────────────────────────────────────────────── */
  const G = window.ncpGlobal || {};
  const T = G.i18n || {};          // translations
  const AJAX = G.ajaxUrl || '/wp-admin/admin-ajax.php';
  const NONCE = G.nonce || '';

  /* ──────────────────────────────────────────────────────────
     Post form to WP AJAX via fetch
  ────────────────────────────────────────────────────────── */
  async function wpPost(action, data) {
    const body = new URLSearchParams({ action, nonce: NONCE, ...data });
    const res = await fetch(AJAX, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  /* ──────────────────────────────────────────────────────────
     Main Instance Factory
  ────────────────────────────────────────────────────────── */
  function createInstance(cfg) {
    // Derived config
    const instanceId = cfg._uid || uid();
    const dir        = cfg.text_direction === 'ltr' ? 'ltr' : 'rtl';
    const floating   = cfg.floating_mode !== false;
    const pos        = cfg.launcher_position === 'left' ? 'left' : 'right';
    const themeMode  = ['light','dark','auto'].includes(cfg.theme_mode) ? cfg.theme_mode : 'auto';
    const persist    = cfg.persist_history !== false;
    const richMd     = cfg.enable_markdown !== false;
    const emojiEnabled = cfg.enable_emoji !== false;
    const notifyEnabled = cfg.enable_notifications !== false;
    const products   = parseProducts(cfg.products_json);

    // Storage keys
    const K = {
      hist:    `ncp:${instanceId}:hist`,
      session: `ncp:${instanceId}:session`,
      open:    `ncp:${instanceId}:open`,
      theme:   `ncp:${instanceId}:theme`,
    };

    function getSession() {
      let s = ls.get(K.session);
      if (!s) { s = uid(); ls.set(K.session, s); }
      return s;
    }

    /* ── Build DOM ── */
const mount = cfg._mount || null;
const root = el('div', buildRootClass());

applyCSSVars(root, cfg);

if (mount && !cfg.floating_mode) {
  mount.innerHTML = '';
  mount.appendChild(root);
} else if (mount && cfg.floating_mode) {
  mount.innerHTML = '';
  document.body.appendChild(root);
} else {
  document.body.appendChild(root);
}

    // Floating launcher
    let launcherEl = null, badgeEl = null;
    if (floating && cfg.show_launcher !== false) {
      launcherEl = el('button', 'ncp-launcher', {
        type: 'button',
        'aria-label': T.launcherOpen || 'باز کردن چت',
        'aria-expanded': 'false',
        'aria-haspopup': 'dialog',
      });
      launcherEl.innerHTML = `
        <span class="ncp-launcher-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </span>
        <span class="ncp-launcher-close-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </span>
      `;
      badgeEl = el('span', 'ncp-badge', { hidden: '' });
      launcherEl.appendChild(badgeEl);
      launcherEl.addEventListener('click', toggleOpen);
      root.appendChild(launcherEl);
    }

    // Panel
    const panel = el('section', 'ncp-panel', {
      role: 'dialog',
      'aria-label': T.headerMenu || 'دستیار هوشمند',
      'aria-modal': 'true',
    });

    // Header
    const headerEl = buildHeader();
    panel.appendChild(headerEl);

    // Subheader (for back navigation)
    const subEl = buildSubheader();
    panel.appendChild(subEl);

    // Views container
    const menuView    = buildMenuView();
    const productsView= buildProductsView();
    const adrView     = buildProductsView(true);
    const chatView    = buildChatView();
    const adrFormView = buildFormView('adr');
    const consultView = buildFormView('consult');
    const successView = buildSuccessView();

    [menuView, productsView, adrView, chatView, adrFormView, consultView, successView].forEach(v => panel.appendChild(v));

    // SR live region
    const srEl = el('p', 'ncp-sr', { 'aria-live': 'polite', 'aria-atomic': 'true' });
    panel.appendChild(srEl);

    root.appendChild(panel);

    /* ── State ── */
    let currentView = 'menu';
    let chatContext = null; // { type: 'company'|'product', product: {id,name}|null }
    let history = [];
    let busy = false;
    let unreadCount = 0;

    /* ── Init ── */
    applyTheme(ls.get(K.theme) || themeMode, false);
    loadHistory();
    showView('menu');

    // Auto-open
    const wasOpen = ls.get(K.open) === '1' || cfg.open_by_default;
    if (wasOpen && floating) setOpen(true, false);
    else if (!floating) setOpen(true, false);

    // Keyboard: Escape to close
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && root.classList.contains('ncp-open')) setOpen(false);
    });

    /* ── Header builder ── */
    function buildHeader() {
      const h = el('header', 'ncp-header');
      const avatar = el('div', 'ncp-header-avatar', { 'aria-hidden': 'true' });
      avatar.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 8v4l3 3"/></svg>`;
      const copy = el('div', 'ncp-header-copy');
      const title = el('span', 'ncp-header-title');
      setText(title, T.headerMenu || 'دستیار هوشمند');
      title.setAttribute('id', `${instanceId}_title`);
      const sub = el('span', 'ncp-header-subtitle');
      const dot = el('span', 'ncp-status-dot');
      const subTxt = el('span');
      setText(subTxt, T.subHeaderOnline || 'آنلاین');
      sub.appendChild(dot);
      sub.appendChild(subTxt);
      copy.appendChild(title);
      copy.appendChild(sub);

      const actions = el('div', 'ncp-header-actions');

      // Theme toggle
      const themeBtn = el('button', 'ncp-icon-btn ncp-theme-btn', { type: 'button', 'aria-label': T.themeToggle || 'تغییر تم' });
      themeBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg>`;
      themeBtn.addEventListener('click', () => {
        const current = root.classList.contains('ncp-dark') ? 'dark' : (root.classList.contains('ncp-auto') ? 'auto' : 'light');
        const next = current === 'light' ? 'dark' : current === 'dark' ? 'auto' : 'light';
        applyTheme(next);
      });

      // Clear chat
      const clearBtn = el('button', 'ncp-icon-btn ncp-clear-btn', { type: 'button', 'aria-label': T.clearChat || 'پاکسازی' });
      clearBtn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>`;
      clearBtn.addEventListener('click', () => { if (confirm('آیا مطمئن هستید؟')) clearHistory(); });

      // Close (floating only)
      if (floating) {
        const closeBtn = el('button', 'ncp-icon-btn ncp-close-btn', { type: 'button', 'aria-label': T.closePanel || 'بستن' });
        closeBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        closeBtn.addEventListener('click', () => setOpen(false));
        actions.appendChild(clearBtn);
        actions.appendChild(themeBtn);
        actions.appendChild(closeBtn);
      } else {
        actions.appendChild(clearBtn);
        actions.appendChild(themeBtn);
      }

      h.appendChild(avatar);
      h.appendChild(copy);
      h.appendChild(actions);

      // Store references for dynamic update
      h._title = title;
      h._subTxt = subTxt;
      return h;
    }

    function setHeaderTitle(title, subtitle) {
      setText(headerEl._title, title);
      if (subtitle !== undefined) setText(headerEl._subTxt, subtitle);
    }

    /* ── Subheader builder ── */
    function buildSubheader() {
      const s = el('div', 'ncp-subheader', { hidden: '' });
      const back = el('button', 'ncp-back-btn', { type: 'button' });
      back.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>`;
      const backTxt = el('span');
      setText(backTxt, T.backButton || 'بازگشت');
      back.appendChild(backTxt);
      const hint = el('span');
      s.appendChild(back);
      s.appendChild(hint);
      back.addEventListener('click', goBack);
      s._hint = hint;
      return s;
    }

    function showSubheader(hint) {
      subEl.removeAttribute('hidden');
      setText(subEl._hint, hint || '');
    }
    function hideSubheader() { subEl.setAttribute('hidden', ''); }

    /* ── Menu view ── */
    function buildMenuView() {
      const v = el('div', 'ncp-view ncp-menu', { id: `${instanceId}_menu`, role: 'main' });
      return v;
    }

    function renderMenu() {
      menuView.innerHTML = '';
      const greeting = el('div', 'ncp-greeting');
      const gTitle = el('div', 'ncp-greeting-title');
      setText(gTitle, T.menuGreeting || 'سلام!');
      const gDesc = el('div', 'ncp-greeting-desc');
      gDesc.innerHTML = `${escT('menuGreetingDesc1', 'به پورتال پشتیبانی نفس فارمد خوش آمدید.')}<br>${escT('menuGreetingDesc2', 'چطور می‌توانم کمکتان کنم؟')}`;
      greeting.appendChild(gTitle);
      greeting.appendChild(gDesc);
      menuView.appendChild(greeting);

      const items = [];
      if (cfg.show_company)  items.push({ id: 'company',  icon: '🏢', title: T.menuCompanyTitle || 'سوال درباره شرکت',  desc: T.menuCompanyDesc || 'تاریخچه، خط مشی و اطلاعات تماس' });
      if (cfg.show_products) items.push({ id: 'products', icon: '💊', title: T.menuProductsTitle || 'سوال درباره محصولات', desc: T.menuProductsDesc || 'اطلاعات دارویی، نحوه مصرف و عوارض' });
      if (cfg.show_adr)      items.push({ id: 'adr',      icon: '⚠️', title: T.menuAdrTitle || 'ثبت عوارض دارویی',      desc: '' });
      if (cfg.show_consult)  items.push({ id: 'consult',  icon: '🗓️', title: T.menuConsultTitle || 'درخواست مشاوره',     desc: '' });

      if (!items.length) {
        const empty = el('p', 'ncp-menu-empty');
        setText(empty, T.menuNoOption || 'هیچ گزینه‌ای برای نمایش فعال نشده است.');
        menuView.appendChild(empty);
        return;
      }

      items.forEach(item => {
        const btn = el('button', 'ncp-menu-btn', { type: 'button', 'data-action': item.id });
        const iconWrap = el('span', 'ncp-menu-btn-icon', { 'aria-hidden': 'true' });
        iconWrap.textContent = item.icon;
        const copy = el('span', 'ncp-menu-btn-copy');
        const titleEl = el('span', 'ncp-menu-btn-title');
        setText(titleEl, item.title);
        copy.appendChild(titleEl);
        if (item.desc) {
          const descEl = el('span', 'ncp-menu-btn-desc');
          setText(descEl, item.desc);
          copy.appendChild(descEl);
        }
        const arrow = el('span', 'ncp-menu-btn-arrow', { 'aria-hidden': 'true' });
        arrow.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>`;
        btn.appendChild(iconWrap);
        btn.appendChild(copy);
        btn.appendChild(arrow);
        btn.addEventListener('click', () => handleMenuAction(item.id));
        menuView.appendChild(btn);
      });
    }

    /* ── Products view ── */
    function buildProductsView(forAdr = false) {
      const v = el('div', `ncp-view ncp-products-view`, { id: `${instanceId}_${forAdr ? 'adr' : 'products'}` });
      v._forAdr = forAdr;
      return v;
    }

    function renderProductsView(v) {
      v.innerHTML = '';
      const prompt = el('p', 'ncp-products-prompt');
      setText(prompt, v._forAdr ? (T.adrPrompt || 'لطفاً دارو را انتخاب کنید:') : (T.productsPrompt || 'لطفاً محصول مورد نظر را انتخاب کنید:'));
      v.appendChild(prompt);

      const grid = el('div', 'ncp-products-grid');
      products.forEach(p => {
        const btn = el('button', 'ncp-product-btn', { type: 'button', 'aria-label': `انتخاب ${p.name}` });
        const icon = el('span', 'ncp-product-icon', { 'aria-hidden': 'true' });
        icon.textContent = '💊';
        const name = el('span');
        setText(name, p.name);
        btn.appendChild(icon);
        btn.appendChild(name);
        btn.addEventListener('click', () => {
          if (v._forAdr) { startAdrForm(p); }
          else { startProductChat(p); }
        });
        grid.appendChild(btn);
      });
      v.appendChild(grid);
    }

    /* ── Chat view ── */
    function buildChatView() {
      const v = el('div', 'ncp-view ncp-chat-view', { role: 'main' });

      const msgs = el('div', 'ncp-messages', { role: 'log', 'aria-live': 'polite', 'aria-relevant': 'additions text', 'aria-label': 'پیام‌های چت' });
      v.appendChild(msgs);

      const warning = el('div', 'ncp-ai-warning', { 'aria-label': T.chatAiWarning || 'هوش مصنوعی ممکن است اشتباه کند.' });
      setText(warning, T.chatAiWarning || 'هوش مصنوعی ممکن است اشتباه کند.');
      v.appendChild(warning);

      // Footer
      const footer = el('div', 'ncp-chat-footer');

      // Emoji
      let emojiPanel = null;
      if (emojiEnabled) {
        const emojis = ['😊','😍','👍','🙏','🔥','❤️','😂','🎯','✅','🤔','👋','🌸'];
        const emojiBtn = el('button', 'ncp-emoji-toggle', { type: 'button', 'aria-label': T.emojiToggle || 'ایموجی' });
        emojiBtn.textContent = '😊';
        emojiPanel = el('div', 'ncp-emoji-panel', { hidden: '', role: 'listbox', 'aria-label': 'انتخاب ایموجی' });
        emojis.forEach(em => {
          const eb = el('button', 'ncp-emoji-btn', { type: 'button', role: 'option' });
          eb.textContent = em;
          eb.addEventListener('click', () => {
            inputEl.value += em;
            inputEl.focus();
            emojiPanel.setAttribute('hidden', '');
            autoResize();
          });
          emojiPanel.appendChild(eb);
        });
        emojiBtn.addEventListener('click', e => {
          e.stopPropagation();
          emojiPanel.hasAttribute('hidden') ? emojiPanel.removeAttribute('hidden') : emojiPanel.setAttribute('hidden', '');
        });
        document.addEventListener('click', () => emojiPanel?.setAttribute('hidden', ''));
        footer.appendChild(emojiBtn);
        footer.appendChild(emojiPanel);
      }

      const inputEl = el('textarea', 'ncp-chat-input', {
        placeholder: T.chatPlaceholder || 'پیام خود را بنویسید...',
        maxlength: '2000',
        rows: '1',
        'aria-label': T.chatPlaceholder || 'پیام خود را بنویسید...',
        'aria-multiline': 'true',
      });
      inputEl.setAttribute('dir', dir);

      function autoResize() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
        inputEl.style.overflowY = inputEl.scrollHeight > 120 ? 'auto' : 'hidden';
      }

      inputEl.addEventListener('input', autoResize);
      inputEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
      });

      const sendBtn = el('button', 'ncp-send-btn', { type: 'button', 'aria-label': T.chatSendAria || 'ارسال پیام' });
      sendBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`;
      sendBtn.addEventListener('click', sendMsg);

      footer.appendChild(inputEl);
      footer.appendChild(sendBtn);
      v.appendChild(footer);

      // Expose internals
      v._msgs   = msgs;
      v._input  = inputEl;
      v._send   = sendBtn;
      v._resize = autoResize;
      return v;
    }

    /* ── Form view ── */
    function buildFormView(type) {
      const isAdr = type === 'adr';
      const v = el('div', `ncp-view ncp-form-view`, { id: `${instanceId}_form_${type}` });
      v._type = type;

      const banner = el('div', 'ncp-adr-banner', { hidden: '' });
      v.appendChild(banner);
      v._banner = banner;

      // Name field
      const nameField = buildField('name', T.formNameLabel || 'نام و نام خانوادگی', T.formNamePlaceholder || 'مثلاً: علی احمدی', 'text', true);
      v.appendChild(nameField.wrap);

      // Phone field
      const phoneField = buildField('phone', T.formPhoneLabel || 'شماره موبایل', T.formPhonePlaceholder || '09121234567', 'tel', true);
      v.appendChild(phoneField.wrap);

      // Description
      const descLabel = isAdr ? (T.formDescAdrLabel || 'شرح عارضه') : (T.formDescConsultLabel || 'موضوع درخواست');
      const descPh    = isAdr ? (T.formDescAdrPlaceholder || 'لطفاً شرح دهید...') : (T.formDescConsultPlaceholder || 'لطفاً بنویسید...');
      const descField = buildTextareaField('description', descLabel, descPh, true);
      v.appendChild(descField.wrap);

      const submitBtn = el('button', 'ncp-submit-btn', { type: 'button' });
      setText(submitBtn, isAdr ? (T.formSubmitAdr || 'ثبت گزارش عوارض') : (T.formSubmitConsult || 'ثبت درخواست مشاوره'));
      v.appendChild(submitBtn);

      submitBtn.addEventListener('click', () => submitForm(v, type));

      v._fields = { name: nameField, phone: phoneField, description: descField };
      v._submit = submitBtn;
      return v;
    }

    function buildField(name, label, placeholder, type, required) {
      const wrap = el('div', 'ncp-field');
      const lbl = el('label', 'ncp-label');
      lbl.textContent = label;
      if (required) { const req = el('span', 'ncp-req'); req.textContent = '*'; lbl.appendChild(req); }
      const input = el('input', 'ncp-input', { type, placeholder, name, autocomplete: 'off', dir });
      if (required) input.setAttribute('required', '');
      const errEl = el('span', 'ncp-field-error', { role: 'alert', 'aria-live': 'assertive' });
      lbl.setAttribute('for', `${instanceId}_${name}`);
      input.setAttribute('id', `${instanceId}_${name}`);
      wrap.appendChild(lbl);
      wrap.appendChild(input);
      wrap.appendChild(errEl);
      return { wrap, input, errEl };
    }

    function buildTextareaField(name, label, placeholder, required) {
      const wrap = el('div', 'ncp-field');
      const lbl = el('label', 'ncp-label');
      lbl.textContent = label;
      if (required) { const req = el('span', 'ncp-req'); req.textContent = '*'; lbl.appendChild(req); }
      const input = el('textarea', 'ncp-textarea', { placeholder, name, dir, rows: '4' });
      if (required) input.setAttribute('required', '');
      const errEl = el('span', 'ncp-field-error', { role: 'alert', 'aria-live': 'assertive' });
      lbl.setAttribute('for', `${instanceId}_${name}`);
      input.setAttribute('id', `${instanceId}_${name}`);
      wrap.appendChild(lbl);
      wrap.appendChild(input);
      wrap.appendChild(errEl);
      return { wrap, input: input, errEl };
    }

    /* ── Success view ── */
    function buildSuccessView() {
      const v = el('div', 'ncp-view ncp-success-view');
      const iconWrap = el('div', 'ncp-success-icon', { 'aria-hidden': 'true' });
      iconWrap.innerHTML = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
      const title = el('h3', 'ncp-success-title');
      setText(title, T.successTitle || 'ثبت موفقیت‌آمیز');
      const desc = el('p', 'ncp-success-desc');
      desc.innerHTML = `${escT('successDesc1', 'اطلاعات شما با موفقیت ثبت شد.')}<br>${escT('successDesc2', 'کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.')}`;
      const backBtn = el('button', 'ncp-success-back', { type: 'button' });
      setText(backBtn, T.successBack || 'بازگشت به منوی اصلی');
      backBtn.addEventListener('click', () => showView('menu'));
      v.appendChild(iconWrap);
      v.appendChild(title);
      v.appendChild(desc);
      v.appendChild(backBtn);
      return v;
    }

    /* ── View navigation ── */
    function showView(viewName) {
      currentView = viewName;
      const map = {
        menu:    menuView,
        products:productsView,
        adr:     adrView,
        chat:    chatView,
        adrForm: adrFormView,
        consult: consultView,
        success: successView,
      };
      Object.values(map).forEach(v => v.classList.remove('ncp-active'));
      const target = map[viewName];
      if (target) target.classList.add('ncp-active');

      // Update header and subheader per view
      switch (viewName) {
        case 'menu':
          setHeaderTitle(T.headerMenu || 'دستیار هوشمند', T.subHeaderOnline || 'آنلاین');
          hideSubheader();
          renderMenu();
          break;
        case 'products':
          setHeaderTitle(T.headerProducts || 'انتخاب محصول', T.subHeaderKnowledge || 'متصل به پایگاه دانش');
          showSubheader();
          renderProductsView(productsView);
          break;
        case 'adr':
          setHeaderTitle(T.headerAdrSelect || 'انتخاب دارو', T.subHeaderKnowledge || 'متصل به پایگاه دانش');
          showSubheader();
          renderProductsView(adrView);
          break;
        case 'chat':
          setHeaderTitle(T.headerMenu || 'دستیار هوشمند', T.subHeaderKnowledge || 'متصل به پایگاه دانش');
          showSubheader(chatContext?.product ? `محصول: ${chatContext.product.name}` : '');
          setTimeout(() => { chatView._input.focus(); scrollToBottom(); }, 50);
          break;
        case 'adrForm':
          setHeaderTitle(T.headerAdrForm || 'ثبت عوارض', T.subHeaderForm || 'اطلاعات را وارد کنید');
          showSubheader();
          break;
        case 'consult':
          setHeaderTitle(T.headerConsultForm || 'درخواست مشاوره', T.subHeaderForm || 'اطلاعات را وارد کنید');
          showSubheader();
          break;
        case 'success':
          setHeaderTitle(T.headerSuccess || 'ثبت موفقیت‌آمیز', '');
          hideSubheader();
          break;
      }
    }

    function goBack() {
      switch (currentView) {
        case 'products': showView('menu'); break;
        case 'adr':      showView('menu'); break;
        case 'chat':
          if (chatContext?.type === 'product') showView('products');
          else showView('menu');
          break;
        case 'adrForm':  showView('adr'); break;
        case 'consult':  showView('menu'); break;
        default:         showView('menu'); break;
      }
    }

    /* ── Action handlers ── */
    function handleMenuAction(action) {
      switch (action) {
        case 'company':
          chatContext = { type: 'company', product: null };
          loadHistory();
          if (!history.length) {
            addBotMessage(
              (T.chatWelcomeCompany || 'سلام! آماده پاسخگویی به سوالات شما درباره **{company}** هستم.')
                .replace('{company}', cfg.company_name || 'شرکت'),
              false
            );
          }
          showView('chat');
          break;
        case 'products':
          if (!products.length) { startCompanyChat(); break; }
          showView('products');
          break;
        case 'adr':
          if (!products.length) { showView('adrForm'); break; }
          showView('adr');
          break;
        case 'consult':
          showView('consult');
          break;
      }
    }

    function startProductChat(product) {
      chatContext = { type: 'product', product };
      loadHistory();
      if (!history.length) {
        addBotMessage(
          (T.chatWelcomeProduct || 'سلام! من دستیار هوشمند **{product}** هستم. هر سوالی دارید بپرسید.')
            .replace('{product}', product.name),
          false
        );
      }
      showView('chat');
    }

    function startAdrForm(product) {
      const banner = adrFormView._banner;
      const bannerTxt = (T.adrBanner || 'گزارش عارضه برای: {product}').replace('{product}', product.name);
      setText(banner, bannerTxt);
      banner.removeAttribute('hidden');
      adrFormView._product = product;
      showView('adrForm');
    }

    /* ── Chat messaging ── */
    function sendMsg() {
      if (busy) return;
      const text = chatView._input.value.trim();
      if (!text) return;
      if (text.length > 2000) { showToast(T.errTooLong || 'پیام بیش از حد طولانی است.'); return; }

      addUserMessage(text);
      chatView._input.value = '';
      chatView._resize();

      const typingEl = addTyping();
      setBusy(true);

      const productId = chatContext?.product?.id || '';
      const payload = {
        message:     text,
        provider:    cfg.provider || 'avalai',
        model:       cfg.model || 'gpt-4o-mini',
        temperature: String(cfg.temperature ?? 0.7),
        max_tokens:  String(cfg.max_tokens ?? 512),
        system_prompt: cfg.system_prompt || '',
        history:     JSON.stringify(history),
        session_id:  getSession(),
        product:     productId,
      };

      const externalUrl = cfg.chat_api_url;
      const doPost = externalUrl
        ? fetchExternal(externalUrl, payload)
        : wpPost('ncp_chat', payload);

      doPost.then(data => {
        typingEl.remove();
        const reply = data.success ? (data.data?.message || '') : (data.data?.message || T.errNetwork || 'خطا در ارتباط');
        if (!data.success) { addBotMessage(reply, false); showToast(reply); return; }
        addBotMessage(reply, true);
        notifyUser(reply);
      }).catch(err => {
        typingEl.remove();
        const errMsg = T.errNetwork || 'خطا در ارتباط با سرور. اتصال اینترنت خود را بررسی کنید.';
        addBotMessage(errMsg, false);
        showToast(errMsg);
        console.warn('[NCP] Chat error:', err);
      }).finally(() => {
        setBusy(false);
        chatView._input.focus();
      });
    }

    async function fetchExternal(url, data) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    }

    function addUserMessage(text) {
      const msgEl = el('div', 'ncp-msg ncp-msg-user');
      const p = el('p');
      setText(p, text);
      msgEl.appendChild(p);
      chatView._msgs.appendChild(msgEl);
      scrollToBottom();
      announce(text);

      history.push({ role: 'user', content: text });
      trimHistory();
      saveHistory();
    }

    function addBotMessage(text, rich) {
      const msgEl = el('div', 'ncp-msg ncp-msg-bot');
      if (rich && richMd) {
        msgEl.innerHTML = md2html(text);
      } else {
        const p = el('p');
        setText(p, text);
        msgEl.appendChild(p);
      }
      chatView._msgs.appendChild(msgEl);
      scrollToBottom();
      announce(text);

      history.push({ role: 'assistant', content: text });
      trimHistory();
      saveHistory();

      if (badgeEl && !root.classList.contains('ncp-open')) {
        unreadCount++;
        badgeEl.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
        badgeEl.removeAttribute('hidden');
      }
    }

    function addTyping() {
      const wrap = el('div', 'ncp-msg ncp-msg-bot');
      const inner = el('div', 'ncp-typing', { 'aria-label': T.typing || 'در حال پاسخ...' });
      for (let i = 0; i < 3; i++) inner.appendChild(el('span', 'ncp-dot', { 'aria-hidden': 'true' }));
      wrap.appendChild(inner);
      chatView._msgs.appendChild(wrap);
      scrollToBottom();
      return wrap;
    }

    function scrollToBottom() {
      const msgs = chatView._msgs;
      msgs.scrollTop = msgs.scrollHeight;
    }

    function setBusy(b) {
      busy = b;
      chatView._input.disabled = b;
      chatView._send.disabled  = b;
    }

    /* ── Form submission ── */
    async function submitForm(formView, type) {
      const isAdr = type === 'adr';
      const fields = formView._fields;

      // Validate
      let valid = true;
      function setErr(field, msg) {
        field.input.classList.toggle('ncp-invalid', !!msg);
        field.errEl.classList.toggle('ncp-visible', !!msg);
        if (msg) { setText(field.errEl, msg); valid = false; }
        else { field.errEl.textContent = ''; }
      }

      const name = fields.name.input.value.trim();
      const phone = fields.phone.input.value.trim();
      const desc = fields.description.input.value.trim();

      setErr(fields.name,        name.length < 2 ? 'نام باید حداقل ۲ کاراکتر باشد' : '');
      setErr(fields.phone,       !/^(\+98|0)?9\d{9}$/.test(phone) ? 'شماره موبایل معتبر نیست' : '');
      setErr(fields.description, desc.length < 5 ? 'توضیحات باید حداقل ۵ کاراکتر باشد' : '');

      if (!valid) return;

      // Loading state
      const btn = formView._submit;
      const origHTML = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<span class="ncp-spinner"></span>`;

      const payload = {
        type:        isAdr ? (T.formTypeAdr || 'گزارش عوارض دارویی') : (T.formTypeConsult || 'درخواست مشاوره'),
        name, phone,
        description: desc,
        product:     formView._product?.name || '',
      };

      try {
        const externalUrl = cfg.submit_api_url;
        let data;
        if (externalUrl) {
          const res = await fetch(externalUrl, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
          data = await res.json();
        } else {
          data = await wpPost('ncp_form_submit', payload);
        }

        if (data.success) {
          // Clear fields
          Object.values(fields).forEach(f => { f.input.value = ''; f.input.classList.remove('ncp-invalid'); });
          showView('success');
        } else {
          showToast(data.data?.message || T.errFormSubmit || 'در ثبت اطلاعات مشکلی پیش آمد.');
        }
      } catch {
        showToast(T.errFormSubmit || 'در ثبت اطلاعات مشکلی پیش آمد.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = origHTML;
      }
    }

    /* ── History management ── */
    function loadHistory() {
      history = [];
      const chatMsgs = chatView._msgs;
      chatMsgs.innerHTML = '';

      if (!persist) return;
      const raw = ls.get(K.hist);
      if (!raw) return;
      try {
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return;
        history = parsed.filter(h => ['user','assistant'].includes(h.role) && typeof h.content === 'string');
        history.forEach(h => {
          if (h.role === 'user') {
            const d = el('div', 'ncp-msg ncp-msg-user');
            const p = el('p'); setText(p, h.content); d.appendChild(p); chatMsgs.appendChild(d);
          } else {
            const d = el('div', 'ncp-msg ncp-msg-bot');
            if (richMd) d.innerHTML = md2html(h.content); else { const p = el('p'); setText(p, h.content); d.appendChild(p); }
            chatMsgs.appendChild(d);
          }
        });
      } catch { ls.del(K.hist); }
    }

    function saveHistory() {
      if (!persist) return;
      ls.set(K.hist, JSON.stringify(history));
    }

    function trimHistory() {
      const maxPairs = Math.max(0, cfg.history_length ?? 6);
      if (maxPairs === 0) { history = []; return; }
      const max = maxPairs * 2;
      if (history.length > max) history = history.slice(-max);
    }

    function clearHistory() {
      history = [];
      ls.del(K.hist);
      chatView._msgs.innerHTML = '';
    }

    /* ── Theme ── */
    function applyTheme(mode, save = true) {
      root.classList.remove('ncp-light', 'ncp-dark', 'ncp-auto');
      root.classList.add(`ncp-${mode}`);
      if (save) ls.set(K.theme, mode);
    }

    /* ── Panel open/close ── */
    function toggleOpen() { setOpen(!root.classList.contains('ncp-open')); }

    function setOpen(open, save = true) {
      root.classList.toggle('ncp-open', open);
      if (launcherEl) {
        launcherEl.setAttribute('aria-expanded', open ? 'true' : 'false');
        launcherEl.setAttribute('aria-label', open ? (T.launcherClose || 'بستن چت') : (T.launcherOpen || 'باز کردن چت'));
      }
      if (open) {
        unreadCount = 0;
        if (badgeEl) badgeEl.setAttribute('hidden', '');
        // Focus first focusable element
        setTimeout(() => { const f = panel.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'); f?.focus(); }, 220);
      } else {
        launcherEl?.focus();
      }
      if (save) ls.set(K.open, open ? '1' : '0');
    }

    /* ── Notifications ── */
    function notifyUser(text) {
      if (!notifyEnabled || !document.hidden || !('Notification' in window)) return;
      if (Notification.permission === 'granted') {
        new Notification(T.menuGreeting || 'پیام جدید', { body: String(text).slice(0, 100) });
      } else if (Notification.permission !== 'denied') {
        Notification.requestPermission();
      }
    }

    /* ── Toast ── */
    function showToast(msg) {
      const existing = panel.querySelector('.ncp-toast');
      if (existing) existing.remove();
      const toast = el('div', 'ncp-toast', { role: 'alert', 'aria-live': 'assertive' });
      setText(toast, msg);
      panel.appendChild(toast);
      setTimeout(() => toast.remove(), 5000);
    }

    /* ── SR announcement ── */
    function announce(text) {
      srEl.textContent = '';
      requestAnimationFrame(() => setText(srEl, String(text).slice(0, 200)));
    }

    /* ── Helpers ── */
    function escT(key, fallback) {
      const s = T[key] || fallback || '';
      return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function parseProducts(json) {
      if (!json) return [];
      try {
        const p = typeof json === 'string' ? JSON.parse(json) : json;
        if (!Array.isArray(p)) return [];
        return p.filter(x => x.id && x.name).map(x => ({ id: String(x.id), name: String(x.name) }));
      } catch { return []; }
    }

    function buildRootClass() {
      const cls = ['ncp-root'];
      if (floating) { cls.push('ncp-floating', `ncp-pos-${pos}`); }
      else { cls.push('ncp-inline'); }
      cls.push(`ncp-${themeMode}`);
      if (dir === 'ltr') cls.push('ncp-ltr');
      return cls.join(' ');
    }
  } // end createInstance

  /* ──────────────────────────────────────────────────────────
     Apply CSS variables from config
  ────────────────────────────────────────────────────────── */
  function applyCSSVars(root, cfg) {
    const map = {
      'ncp-primary':       cfg.theme_primary,
      'ncp-primary-h':     cfg.theme_primary_hover,
      'ncp-bg':            cfg.theme_bg_base,
      'ncp-card':          cfg.theme_bg_card,
      'ncp-border':        cfg.theme_border,
      'ncp-text':          cfg.theme_text_base,
      'ncp-muted':         cfg.theme_text_muted,
      'ncp-ctrl-bg':       cfg.theme_control_bg,
      'ncp-ctrl-h':        cfg.theme_control_hover,
      'ncp-ctrl-text':     cfg.theme_control_text,
      'ncp-font':          cfg.font_family,
      'ncp-fs-h':          cfg.font_size_heading,
      'ncp-fs-body':       cfg.font_size_body,
      'ncp-fs-sm':         cfg.font_size_caption,
      'ncp-panel-w':       cfg.panel_width,
      'ncp-panel-h':       cfg.panel_height,
    };
    Object.entries(map).forEach(([k, v]) => { if (v) root.style.setProperty(`--${k}`, v); });

    // Compute primary-rgb for shadows
    if (cfg.theme_primary) {
      const rgb = hexToRgb(cfg.theme_primary);
      if (rgb) root.style.setProperty('--ncp-primary-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
    }
  }

  function hexToRgb(hex) {
    const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return m ? { r: parseInt(m[1],16), g: parseInt(m[2],16), b: parseInt(m[3],16) } : null;
  }

  /* ──────────────────────────────────────────────────────────
     Bootstrap: mount all waiting instances
  ────────────────────────────────────────────────────────── */
  function mountFromConfig(cfg) {
    // Find matching mount point (or create floating)
    const mounts = Array.from(document.querySelectorAll('.ncp-mount'));
    let mountEl = mounts.find(m => {
      try { return JSON.parse(m.dataset.ncpConfig || '{}')._uid === cfg._uid; } catch { return false; }
    });

    if (!mountEl) return;  // No matching mount
    mountEl.removeAttribute('data-ncp-config'); // Clean up

    createInstance(cfg);
    mountEl.remove(); // Mount point is replaced by floating widget
  }

  function boot() {
    const mounts = Array.from(document.querySelectorAll('.ncp-mount[data-ncp-config]'));
    mounts.forEach(m => {
      try {
        const cfg = JSON.parse(m.getAttribute('data-ncp-config') || '{}');
        createInstance(cfg);
        m.remove();
      } catch (e) {
        console.error('[NCP] Failed to init instance:', e);
      }
    });
  }

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootNcp);
} else {
    bootNcp();
}


  // Push API for future dynamic adds
function parseMountConfig(mount) {
  try {
    const raw = mount.getAttribute('data-ncp-config') || '{}';
    const cfg = JSON.parse(raw);
    cfg._mount = mount;

    if (!cfg._uid) {
      cfg._uid = mount.getAttribute('data-ncp-uid') || uid();
      mount.setAttribute('data-ncp-uid', cfg._uid);
    }

    return cfg;
  } catch (e) {
    console.error('[NCP] Invalid mount config:', e);
    return null;
  }
}

function bootNcp() {
  const mounted = new WeakSet();

  document.querySelectorAll('.ncp-mount[data-ncp-config]').forEach(mount => {
    if (mounted.has(mount)) return;
    if (mount.dataset.ncpBooted === '1') return;

    const cfg = parseMountConfig(mount);
    if (!cfg) return;

    mount.dataset.ncpBooted = '1';
    mounted.add(mount);

    createInstance(cfg);
  });

  if (Array.isArray(window.ncpInstances)) {
    window.ncpInstances.forEach(cfg => {
      if (!cfg || cfg._booted) return;
      cfg._booted = true;
      createInstance(cfg);
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootNcp);
} else {
  bootNcp();
}
}

})();
