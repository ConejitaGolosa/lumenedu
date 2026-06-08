/* LumenEdu — main.js */

document.addEventListener('DOMContentLoaded', () => {

  // ── MOBILE NAV ───────────────────────────────────────────────
  const toggle = document.querySelector('.nav-toggle');
  const links  = document.querySelector('.nav-links');

  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open);
    });

    document.addEventListener('click', e => {
      if (!toggle.contains(e.target) && !links.contains(e.target)) {
        links.classList.remove('open');
        toggle.classList.remove('open');
      }
    });

    // Close on ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        links.classList.remove('open');
        toggle.classList.remove('open');
      }
    });
  }

  // ── NAV SCROLL BUTTON ────────────────────────────────────────
  const navLinksEl  = document.querySelector('.nav-links');
  const navScrollBtn = document.getElementById('navScrollBtn');
  const navWrap     = document.querySelector('.nav-links-wrap');

  if (navLinksEl && navScrollBtn) {
    function updateScrollBtn() {
      const hasOverflow = navLinksEl.scrollWidth > navLinksEl.clientWidth + 2;
      const atEnd = navLinksEl.scrollLeft + navLinksEl.clientWidth >= navLinksEl.scrollWidth - 8;

      if (!hasOverflow) {
        navScrollBtn.classList.remove('visible');
        navWrap?.classList.remove('has-overflow');
        return;
      }

      navScrollBtn.classList.add('visible');
      navWrap?.classList.toggle('has-overflow', !atEnd);
      navScrollBtn.innerHTML  = atEnd ? '&#8249;' : '&#8250;'; // ‹ ›
      navScrollBtn.title      = atEnd ? 'Volver al inicio' : 'Más opciones';
    }

    updateScrollBtn();
    navLinksEl.addEventListener('scroll', updateScrollBtn);
    window.addEventListener('resize', updateScrollBtn);

    navScrollBtn.addEventListener('click', () => {
      const atEnd = navLinksEl.scrollLeft + navLinksEl.clientWidth >= navLinksEl.scrollWidth - 8;
      navLinksEl.scrollBy({ left: atEnd ? -navLinksEl.scrollWidth : 180, behavior: 'smooth' });
    });
  }

  // ── ACTIVE NAV LINK ──────────────────────────────────────────
  const currentPage = new URLSearchParams(location.search).get('page') || 'viewHome';
  document.querySelectorAll('.nav-links a[href]').forEach(a => {
    try {
      const href = new URLSearchParams(new URL(a.href, location.origin).search).get('page');
      if (href === currentPage) a.classList.add('active');
    } catch (_) {}
  });

  // ── AUTO-DISMISS FLASH MESSAGES ──────────────────────────────
  document.querySelectorAll('.msg-error, .msg-ok').forEach(msg => {
    setTimeout(() => {
      msg.style.transition = 'opacity .45s ease, transform .45s ease';
      msg.style.opacity    = '0';
      msg.style.transform  = 'translateY(-4px)';
      setTimeout(() => msg.remove(), 450);
    }, 4500);
  });

  // ── DISABLE FORM BUTTON ON SUBMIT ────────────────────────────
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('button[type="submit"], input[type="submit"]');
      if (btn) {
        setTimeout(() => {
          btn.disabled = true;
          if (btn.tagName === 'BUTTON') {
            btn.dataset.original = btn.textContent;
            btn.textContent = 'Enviando…';
          } else {
            btn.dataset.original = btn.value;
            btn.value = 'Enviando…';
          }
        }, 0);
      }
    });
  });

  // ── REPLY TOGGLE (video/foro comments) ───────────────────────
  document.querySelectorAll('.reply-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.nextElementSibling;
      if (form && form.classList.contains('reply-form')) {
        const open = form.classList.toggle('open');
        btn.textContent = open ? 'Cancelar' : 'Responder';
        if (open) form.querySelector('textarea')?.focus();
      }
    });
  });

  // ── FORUM CREATE PANEL TOGGLE ─────────────────────────────────
  const panelToggle = document.querySelector('.create-panel-toggle');
  const panelBody   = document.querySelector('.create-panel-body');

  if (panelToggle && panelBody) {
    panelToggle.addEventListener('click', () => {
      const open = panelBody.classList.toggle('open');
      panelToggle.classList.toggle('open', open);
    });
  }

  // ── CONFIRM DANGEROUS ACTIONS ─────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── PRIVACY SELECT AUTO-SUBMIT (mis videos) ───────────────────
  document.querySelectorAll('.privacy-select').forEach(sel => {
    sel.addEventListener('change', () => sel.closest('form').submit());
  });

});
