(() => {
  const body = document.body;
  const header = document.querySelector('[data-header]');
  const menuButton = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  const progressBar = document.querySelector('.scroll-progress span');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const closeMenu = () => {
    body.classList.remove('menu-open');
    menuButton?.setAttribute('aria-expanded', 'false');
    menuButton?.setAttribute('aria-label', 'Otvoriť menu');
  };

  menuButton?.addEventListener('click', () => {
    const willOpen = !body.classList.contains('menu-open');
    body.classList.toggle('menu-open', willOpen);
    menuButton.setAttribute('aria-expanded', String(willOpen));
    menuButton.setAttribute('aria-label', willOpen ? 'Zavrieť menu' : 'Otvoriť menu');
  });

  nav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });

  const updateScrollUI = () => {
    const top = window.scrollY || document.documentElement.scrollTop;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const value = max > 0 ? (top / max) * 100 : 0;

    if (progressBar) progressBar.style.width = `${value}%`;
    header?.classList.toggle('is-scrolled', top > 18);
  };

  updateScrollUI();
  window.addEventListener('scroll', updateScrollUI, { passive: true });
  window.addEventListener('resize', updateScrollUI);

  const revealItems = document.querySelectorAll('.reveal');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -9% 0px', threshold: 0.08 });

    revealItems.forEach((item, index) => {
      item.style.transitionDelay = `${Math.min(index % 4, 3) * 70}ms`;
      revealObserver.observe(item);
    });
  }

  const tabs = document.querySelectorAll('.program-tab');
  const panels = document.querySelectorAll('.program-panel');

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const panelId = tab.dataset.tab;

      tabs.forEach((button) => {
        const active = button === tab;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', String(active));
      });

      panels.forEach((panel) => {
        const active = panel.id === panelId;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
    });
  });

  const observedSections = document.querySelectorAll('main section[id]');
  const navigationLinks = document.querySelectorAll('.main-nav a[href^="#"]:not(.nav-pill)');

  if ('IntersectionObserver' in window) {
    const navObserver = new IntersectionObserver((entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

      if (!visible) return;
      navigationLinks.forEach((link) => {
        link.classList.toggle('is-active', link.getAttribute('href') === `#${visible.target.id}`);
      });
    }, { rootMargin: '-25% 0px -60% 0px', threshold: [0.01, 0.15, 0.35] });

    observedSections.forEach((section) => navObserver.observe(section));
  }

  const parallax = document.querySelector('[data-parallax]');
  const hero = document.querySelector('.hero');

  if (parallax && hero && !reduceMotion && window.matchMedia('(pointer: fine)').matches) {
    hero.addEventListener('pointermove', (event) => {
      const rect = hero.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      parallax.style.transform = `translate3d(${x * 17}px, ${y * 13}px, 0)`;
    });

    hero.addEventListener('pointerleave', () => {
      parallax.style.transform = 'translate3d(0, 0, 0)';
    });
  }

  const newsletterForm = document.querySelector('[data-newsletter-form]');
  const formStatus = document.querySelector('[data-form-status]');

  newsletterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const email = new FormData(newsletterForm).get('email');

    if (typeof email !== 'string' || !email.includes('@')) {
      if (formStatus) formStatus.textContent = 'Skontroluj, prosím, e-mailovú adresu.';
      return;
    }

    if (formStatus) {
      formStatus.textContent = 'Formulár funguje ako ukážka. Pri nasadení ho prepojíme s newsletterovým nástrojom.';
    }
  });
})();


/* =========================================================================
   Mapa diel (Mapbox GL) — nahrádza pôvodnú dekoratívnu SVG schému bodmi
   z reálnych súradníc zadaných v administrácii (nox_miesto).
   ========================================================================= */
(() => {
  const mapEl = document.getElementById('nox-site-map');
  const config = window.NOX_SITE_MAP || { token: '', style: '', miesta: [], diela: [] };
  if (!mapEl) return;

  const cards = [...document.querySelectorAll('.installation-card[data-work]')];
  const cardByWork = new Map(cards.map((card) => [card.dataset.work, card]));

  const markers = {};
  let map = null;

  const highlightCard = (workId) => {
    cards.forEach((card) => card.classList.toggle('is-map-active', card.dataset.work === workId));
  };

  const dielaAt = (miestoId) => config.diela.filter((d) => String(d.miestoId) === String(miestoId));

  const popupHtml = (miesto) => {
    const items = dielaAt(miesto.id);
    const list = items.length
      ? items.map((d) => `<a href="#work-${d.id}">${d.nazov}</a>`).join('')
      : '';
    return `<strong>${miesto.nazov}</strong>${miesto.adresa ? `<span>${miesto.adresa}</span>` : ''}${list}`;
  };

  const focusMiesto = (miestoId) => {
    const marker = markers[miestoId];
    if (!marker || !map) return;
    map.flyTo({ center: marker.getLngLat(), zoom: Math.max(map.getZoom(), 16), duration: 600 });
    if (!marker.getPopup().isOpen()) marker.togglePopup();
  };

  if (!config.token || typeof mapboxgl === 'undefined') {
    mapEl.innerHTML = '<div style="padding:20px;font-family:monospace;font-size:12px;color:#efeedc">Mapa nie je nastavená. V administrácii choď do NOX:ART &rsaquo; Nastavenia mapy a vlož Mapbox access token.</div>';
    return;
  }

  mapboxgl.accessToken = config.token;
  const pts = config.miesta.filter((m) => m.lat != null && m.lng != null);
  const center = pts.length ? [pts[0].lng, pts[0].lat] : [18.6045, 48.7715];

  map = new mapboxgl.Map({
    container: mapEl,
    style: config.style || 'mapbox://styles/mapbox/dark-v11',
    center,
    zoom: 14,
  });
  map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'bottom-right');

  map.on('load', () => {
    pts.forEach((m) => {
      const el = document.createElement('div');
      el.className = 'site-marker';
      const marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
        .setLngLat([m.lng, m.lat])
        .setPopup(new mapboxgl.Popup({ offset: 26 }).setHTML(popupHtml(m)))
        .addTo(map);
      markers[m.id] = marker;
      el.addEventListener('click', () => {
        const first = dielaAt(m.id)[0];
        if (first) highlightCard(String(first.id));
      });
    });

    if (pts.length > 1) {
      const bounds = new mapboxgl.LngLatBounds();
      pts.forEach((m) => bounds.extend([m.lng, m.lat]));
      map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 0 });
    }
  });

  document.querySelectorAll('[data-show-on-map]').forEach((link) => {
    link.addEventListener('click', (event) => {
      const workId = link.dataset.showOnMap;
      const card = cardByWork.get(workId);
      const miestoId = card?.dataset.miesto;
      if (!miestoId) { event.preventDefault(); return; }
      highlightCard(workId);
      focusMiesto(miestoId);
    });
  });
})();
