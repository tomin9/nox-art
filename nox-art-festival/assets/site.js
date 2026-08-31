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

  // Maximálny scroll sa mení len pri zmene veľkosti okna – čítať ho pri
  // každom scrolle by nútilo prehliadač prepočítať layout v každom snímku.
  let maxScrollUI = 0;
  let lastProgress = -1;
  let lastScrolled = null;

  const measureScrollUI = () => {
    maxScrollUI = document.documentElement.scrollHeight - window.innerHeight;
  };

  const updateScrollUI = () => {
    const top = window.scrollY || document.documentElement.scrollTop;
    const value = maxScrollUI > 0 ? (top / maxScrollUI) * 100 : 0;

    // Zapisovať do štýlu len keď sa hodnota naozaj zmenila (inak zbytočné
    // prekresľovanie pri každom snímku).
    const rounded = Math.round(value * 10) / 10;
    if (progressBar && rounded !== lastProgress) {
      progressBar.style.width = `${rounded}%`;
      lastProgress = rounded;
    }
    const scrolledState = top > 18;
    if (header && scrolledState !== lastScrolled) {
      header.classList.toggle('is-scrolled', scrolledState);
      lastScrolled = scrolledState;
    }
  };

  measureScrollUI();
  updateScrollUI();

  let scrollUITicking = false;
  window.addEventListener('scroll', () => {
    if (scrollUITicking) return;
    scrollUITicking = true;
    requestAnimationFrame(() => { updateScrollUI(); scrollUITicking = false; });
  }, { passive: true });
  window.addEventListener('resize', () => { measureScrollUI(); updateScrollUI(); });
  window.addEventListener('load', measureScrollUI);

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
    // Rozmery hero sekcie čítame len pri vstupe kurzora a pri zmene okna –
    // nie pri každom pohybe myši (to by nútilo prepočet layoutu stovky-krát
    // za sekundu). Samotné posunutie sa zapisuje raz za snímok cez rAF.
    let heroRect = null;
    let pending = null;
    let parallaxTicking = false;

    const refreshHeroRect = () => { heroRect = hero.getBoundingClientRect(); };

    hero.addEventListener('pointerenter', refreshHeroRect);
    window.addEventListener('resize', () => { heroRect = null; });

    hero.addEventListener('pointermove', (event) => {
      if (!heroRect) refreshHeroRect();
      pending = event;
      if (parallaxTicking) return;
      parallaxTicking = true;
      requestAnimationFrame(() => {
        const x = (pending.clientX - heroRect.left) / heroRect.width - 0.5;
        const y = (pending.clientY - heroRect.top) / heroRect.height - 0.5;
        parallax.style.transform = `translate3d(${(x * 17).toFixed(1)}px, ${(y * 13).toFixed(1)}px, 0)`;
        parallaxTicking = false;
      });
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

  // Mapa má pružnú výšku (dopĺňa zvyšné miesto v karte), takže pri zmene
  // veľkosti okna jej treba povedať, nech si prepočíta plátno – inak by
  // ostalo roztiahnuté v pôvodnom pomere a rozmazané.
  let mapResizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(mapResizeTimer);
    mapResizeTimer = setTimeout(() => map.resize(), 200);
  });

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


/* =========================================================================
   "Stack" efekt sekcií – panely v .stack-group sú position:fixed, teda
   sami sa nikdy neposúvajú. Ten, čo je práve "na rade" odísť, dostane
   transform:translateY() a vysunie sa hore preč; panel pod ním celý čas
   stojí na mieste a len sa mu mení rozostrenie (zaostruje sa presne
   podľa toho, ako ten navrchu odchádza). Po prejdení celej skupiny sa
   posledný panel "uvoľní" (position:absolute v rámci skupiny), aby
   normálne odscrolloval preč spolu so zvyškom stránky.
   ========================================================================= */
(() => {
  const group = document.querySelector('[data-stack-group]');
  if (!group) return;
  const panels = [...group.querySelectorAll(':scope > .stack-panel')];
  if (!panels.length) return;

  const inners = panels.map((panel) => panel.querySelector(':scope > .stack-inner') || panel);
  const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

  const STEP = 30;      // kaskádovité odsadenie spodných hrán kariet
  /* Karta, ktorá sa práve odkrýva, sa "zaostruje" priehľadnosťou a jemným
     priblížením – NIE rozostrením. filter:blur() na celoobrazovkovej vrstve
     musí prehliadač prekresliť pri každej zmene hodnoty a jeho cena rastie
     s plochou, takže spôsoboval sekanie. Opacity aj transform naproti tomu
     zvláda grafická karta priamo na hotovej vrstve, bez prekresľovania. */
  const MIN_FADE = 0.5;    // priehľadnosť obsahu na začiatku odkrývania
  const MAX_ZOOM = 0.02;   // o koľko je obsah na začiatku zmenšený (2 %)

  /* Odchod karty nie je lineárny: čím je karta vyššie, tým ide rýchlejšie.
     Miešame lineárny priebeh s parabolickým (x²) – EASE určuje, aký podiel
     má parabola. Pri 0 je pohyb presne lineárny, pri 1 čisto parabolický
     (to už pôsobí prudko); 0.35 je jemné zrýchlenie, ktoré je cítiť, ale
     nevyzerá ako trhnutie. Krivka vždy začína v 0 a končí v 1, takže
     nadväznosť medzi kartami zostáva presná. */
  const EASE = 0.35;
  const ease = (t) => t * (1 - EASE) + t * t * EASE;

  // Posledné zapísané hodnoty – do štýlov zapisujeme len pri skutočnej zmene.
  // Bez toho by sme pri každom snímku prepisovali 7 transformov a 7 filtrov,
  // čo prehliadač núti stále znova prekresľovať.
  const prev = panels.map(() => ({ transform: null, focus: null, released: null, hint: null }));

  let groupTop = 0;
  let vh = 0;
  let maxScroll = 0;
  let enabled = false;

  /* Vypnutý režim: karty sa vrátia do normálneho toku stránky (žiadne
     position:fixed, žiadne rozostrenie). Používa sa pri "prefers-reduced-
     motion", kde by zamrznuté karty inak znemožnili dostať sa k obsahu. */
  const disable = () => {
    enabled = false;
    group.classList.add('stack-off');
    group.style.height = '';
    panels.forEach((panel, i) => {
      panel.style.height = '';
      panel.style.transform = '';
      panel.style.borderRadius = '';
      panel.style.zIndex = '';
      panel.style.willChange = '';
      panel.classList.remove('is-released');
      inners[i].style.filter = '';
      inners[i].style.opacity = '';
      inners[i].style.transform = '';
      inners[i].style.willChange = '';
      prev[i].transform = prev[i].focus = prev[i].released = prev[i].hint = null;
    });
  };

  /* Rozmery čítame len pri štarte a pri zmene okna – nikdy nie počas
     scrollovania. Výšku skupiny aj kariet nastavujeme v pixeloch z
     window.innerHeight, aby CSS aj výpočty v JS vychádzali z tej istej
     hodnoty (predtým bolo CSS v jednotkách svh, JS v innerHeight – na
     mobiloch sa tie dve čísla líšia a posledná karta sa uvoľňovala
     v nesprávnom bode). */
  const measure = () => {
    enabled = true;
    group.classList.remove('stack-off');

    vh = window.innerHeight;
    groupTop = group.getBoundingClientRect().top + window.scrollY;
    group.style.height = `${(panels.length + 1) * vh}px`;
    maxScroll = panels.length * vh;

    const n = panels.length;
    panels.forEach((panel, i) => {
      const fromEnd = n - 1 - i;
      panel.style.zIndex = String(n - i);
      panel.style.height = `${vh - fromEnd * STEP}px`;
      panel.style.borderRadius = fromEnd === 0 ? '0' : '';
    });

  };

  const render = () => {
    if (!enabled) return;

    const scrolled = Math.min(Math.max(window.scrollY - groupTop, 0), maxScroll);
    // Index karty, ktorá je práve "na rade" odísť.
    const activeIdx = Math.min(Math.floor(scrolled / vh), panels.length - 1);

    for (let i = 0; i < panels.length; i++) {
      const isLast = i === panels.length - 1;
      const state = prev[i];

      const raw = isLast ? 0 : Math.min(Math.max((scrolled - i * vh) / vh, 0), 1);
      const depart = ease(raw);
      const transform = depart > 0 ? `translate3d(0,${(-depart * 100).toFixed(2)}%,0)` : '';
      if (transform !== state.transform) {
        panels[i].style.transform = transform;
        state.transform = transform;
      }

      // GPU vrstvu si vopred pripravíme len pri kartách okolo tej aktívnej –
      // držať ju pre všetkých 7 naraz je zbytočne pamäťovo náročné.
      const hint = (i === activeIdx || i === activeIdx + 1) ? 'transform' : '';
      if (hint !== state.hint) {
        panels[i].style.willChange = hint;
        state.hint = hint;
      }

      /* "Zaostrovanie" dostáva VÝHRADNE karta, ktorá sa práve odkrýva spod tej
         odchádzajúcej – z ostatných vidno len ~30px prúžok, kde by efekt nemal
         žiadny vizuálny prínos a stál by výkon.
         focus = 0 na začiatku odkrývania, 1 keď je karta úplne odkrytá. */
      const focus = i === activeIdx + 1
        ? ease(Math.min(Math.max(scrolled / vh - activeIdx, 0), 1))
        : 1;   // ostatné karty nechávame bez štýlov – žiadne vrstvy navyše
      // Zaokrúhlenie na stotinu: bráni zbytočným zápisom pri mikropohyboch
      // kolieska, ale je dosť jemné na to, aby prechod pôsobil spojito.
      const focusStep = Math.round(focus * 100) / 100;
      if (focusStep !== state.focus) {
        if (focusStep >= 1) {
          inners[i].style.opacity = '';
          inners[i].style.transform = '';
          inners[i].style.willChange = '';
        } else {
          inners[i].style.opacity = (MIN_FADE + (1 - MIN_FADE) * focusStep).toFixed(3);
          inners[i].style.transform = `scale(${(1 - MAX_ZOOM * (1 - focusStep)).toFixed(4)})`;
          // will-change zapíname len na tú jednu kartu, ktorá sa práve mení.
          inners[i].style.willChange = 'opacity, transform';
        }
        state.focus = focusStep;
      }

      if (isLast) {
        const released = scrolled >= maxScroll - 0.5;
        if (released !== state.released) {
          panels[i].classList.toggle('is-released', released);
          state.released = released;
        }
      }
    }
  };

  const setup = () => {
    if (motionQuery.matches) { disable(); return; }
    measure();
    render();
  };

  setup();

  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { render(); ticking = false; });
  }, { passive: true });

  /* Na mobiloch mení skrývanie/zobrazovanie adresného riadku výšku okna a
     spúšťa resize aj počas bežného scrollovania. Prepočítavať pri tom celý
     zásobník by trhalo scroll, preto reagujeme len na skutočné zmeny
     (iná šírka, alebo zmena výšky väčšia než typický adresný riadok). */
  let resizeTimer = null;
  let lastW = window.innerWidth;
  let lastH = window.innerHeight;
  window.addEventListener('resize', () => {
    const w = window.innerWidth;
    const h = window.innerHeight;
    if (w === lastW && Math.abs(h - lastH) < 120) return;
    lastW = w;
    lastH = h;
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { setup(); }, 150);
  });

  if (typeof motionQuery.addEventListener === 'function') {
    motionQuery.addEventListener('change', setup);
  }

  // Po načítaní obrázkov/fontov sa môže zmeniť pozícia skupiny.
  window.addEventListener('load', () => { if (!motionQuery.matches) { measure(); render(); } });
})();
