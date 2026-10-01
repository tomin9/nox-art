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

  // Dlaždice v zozname – zvýrazňuje sa tá, ktorá patrí k značke na mape.
  const tiles = [...document.querySelectorAll('.gallery-tile[data-miesto]')];

  const markers = {};
  let map = null;

  const highlightTile = (tile) => {
    tiles.forEach((item) => item.classList.toggle('is-map-active', item === tile));
  };

  const dielaAt = (miestoId) => config.diela.filter((d) => String(d.miestoId) === String(miestoId));

  /* Farba značky podľa kategórie diela, ktoré na mieste stojí – každá
     kategória má svoju, aby bolo na mape vidieť, o aký typ obsahu ide.
     Miesto bez kategórie si necháva pôvodnú ružovo-oranžovú z CSS. */
  const markerColor = (miesto) => {
    const farby = config.farby || {};
    // Najprv kategória samotného miesta (partnerský podnik ju má vlastnú),
    // potom kategória diela, ktoré na mieste stojí.
    const zdroje = [miesto, ...dielaAt(miesto.id)];
    for (const zdroj of zdroje) {
      for (const slug of zdroj.kategorie || []) {
        if (farby[slug]) return farby[slug];
      }
    }
    return '';
  };

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
      /* Kvapka je samostatný vnútorný prvok, nie samotná značka: Mapbox si
         na značku zapisuje vlastný transform (posun po mape) a prepísal by
         tým otočenie – číslo potom zostalo šikmo. */
      el.appendChild(document.createElement('i'));
      const color = markerColor(m);
      if (color) el.style.setProperty('--pin', color);

      // Číslo v značke je to isté, aké má položka na dlaždici – prideľuje
      // ho server, aby sa mapa a zoznam nikdy nerozišli.
      const cislo = (config.cisla || {})[m.id];
      if (cislo) {
        const label = document.createElement('b');
        label.textContent = cislo;
        el.appendChild(label);
      }
      const marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
        .setLngLat([m.lng, m.lat])
        .setPopup(new mapboxgl.Popup({ offset: 26 }).setHTML(popupHtml(m)))
        .addTo(map);
      markers[m.id] = marker;
      el.addEventListener('click', () => {
        // Dlaždicou miesta je buď prvé dielo na ňom, alebo samotné miesto
        // (partnerský podnik), podľa toho, čo je v zozname.
        const first = dielaAt(m.id)[0];
        highlightTile(
          (first && document.getElementById(`work-${first.id}`)) ||
          document.getElementById(`miesto-${m.id}`)
        );
      });
    });

    if (pts.length > 1) {
      const bounds = new mapboxgl.LngLatBounds();
      pts.forEach((m) => bounds.extend([m.lng, m.lat]));
      map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 0 });
    }
  });

  /* Prejdenie kurzorom nad dlaždicou zvýrazní jej značku na mape – nie je
     na to treba klikať. Značku hľadáme až pri prejdení, lebo v čase, keď
     sa tieto poslucháče pripájajú, mapa ešte značky vytvorené nemá. */
  const hoverMarker = (miestoId, on) => {
    const marker = markers[miestoId];
    if (marker) marker.getElement().classList.toggle('is-hovered', on);
  };

  tiles.forEach((tile) => {
    const miestoId = tile.dataset.miesto;
    if (!miestoId) return;
    tile.addEventListener('pointerenter', () => hoverMarker(miestoId, true));
    tile.addEventListener('pointerleave', () => hoverMarker(miestoId, false));
  });

  document.querySelectorAll('[data-show-on-map]').forEach((link) => {
    link.addEventListener('click', (event) => {
      /* Mapa je na tej istej stránke a pri zozname stále vidno – skok na
         kotvu #mapa by len zbytočne odscrolloval stránku. Tento kód beží
         len vtedy, keď mapa na stránke naozaj je (inak sa poslucháč vôbec
         nepripojí), takže odkaz je bezpečné zastaviť vždy. */
      event.preventDefault();

      // Miesto berieme z dlaždice, v ktorej odkaz je – položka nemusí byť
      // dielo (partnerský podnik je priamo miesto a vlastné dielo nemá).
      const tile = link.closest('[data-miesto]');
      const miestoId = tile?.dataset.miesto;
      if (!miestoId) return;
      highlightTile(tile);
      focusMiesto(miestoId);
    });
  });
})();



/* =========================================================================
   Filtrovanie podľa kategórie (Všetky / Inštalácie / Nové sgrafitá / …).
   Všetky položky sú vyrenderované už na serveri – prepínanie len skrýva
   a odkrýva, takže je okamžité, bez načítavania a bez AJAX volaní.
   ========================================================================= */
(() => {
  document.querySelectorAll('[data-filter-group]').forEach((bar) => {
    const group = bar.dataset.filterGroup;
    const target = document.querySelector(`[data-filter-target="${group}"]`);
    if (!target) return;

    const chips = [...bar.querySelectorAll('.filter-chip')];
    const subBars = [...document.querySelectorAll(`[data-filter-parent="${group}"]`)];
    const items = [...target.querySelectorAll('[data-cat]')];
    const emptyNote = document.querySelector(`[data-filter-empty="${group}"]`);

    // Hlavná skupina (Diela / Sprievodný program / Podniky) a prípadné
    // spresnenie v jej druhom rade. Položky nesú aj nadradené kategórie,
    // takže na hlavnú skupinu sadne všetko, čo pod ňu patrí.
    let parent = chips.find((chip) => chip.classList.contains('is-active'))?.dataset.filter || '';
    let child = '';

    const apply = () => {
      const filter = child || parent;
      items.forEach((item) => {
        const match = !filter || (item.dataset.cat || '').split(' ').includes(filter);
        item.classList.toggle('is-filtered-out', !match);
      });

      if (emptyNote) {
        // offsetParent je null aj pre položky v skrytom kontajneri, takže sa
        // pýtame na to, čo používateľ naozaj vidí.
        const visible = items.some((item) => !item.classList.contains('is-filtered-out') && item.offsetParent !== null);
        emptyNote.hidden = visible;
      }
    };

    const syncSubBars = () => {
      subBars.forEach((sub) => {
        const mine = sub.dataset.filterSub === parent;
        sub.hidden = !mine;
        if (!mine) {
          sub.querySelectorAll('.filter-chip').forEach((chip) => {
            chip.classList.remove('is-active');
            chip.setAttribute('aria-pressed', 'false');
          });
        }
      });
    };

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        chips.forEach((other) => {
          const active = other === chip;
          other.classList.toggle('is-active', active);
          other.setAttribute('aria-pressed', String(active));
        });
        parent = chip.dataset.filter || '';
        child = '';
        syncSubBars();
        apply();
      });
    });

    subBars.forEach((sub) => {
      const subChips = [...sub.querySelectorAll('.filter-chip')];
      subChips.forEach((chip) => {
        chip.addEventListener('click', () => {
          // Druhé kliknutie na to isté spresnenie ho zruší a vráti celú skupinu.
          const turnOff = chip.classList.contains('is-active');
          subChips.forEach((other) => {
            const active = !turnOff && other === chip;
            other.classList.toggle('is-active', active);
            other.setAttribute('aria-pressed', String(active));
          });
          child = turnOff ? '' : (chip.dataset.filter || '');
          apply();
        });
      });
    });

    syncSubBars();
    apply();
  });
})();


/* =========================================================================
   Roletka: hero drží celé okno a prvou obrazovkou scrollovania sa vysunie
   hore, zatiaľ čo obsah pod ňou stojí na mieste. Obidve vrstvy sa posúvajú
   iba cez transform – to je jediná vlastnosť, ktorú prehliadač zvládne bez
   prekresľovania, takže scrollovanie zostáva plynulé.
   ========================================================================= */
(() => {
  const curtain = document.querySelector('[data-curtain]');
  const below = document.querySelector('[data-curtain-below]');
  if (!curtain || !below) return;

  const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  let vh = 0;
  let last = null;
  let enabled = false;

  const disable = () => {
    enabled = false;
    document.body.classList.add('curtain-off');
    curtain.style.transform = '';
    below.style.transform = '';
    curtain.style.willChange = '';
    below.style.willChange = '';
    last = null;
  };

  const measure = () => {
    enabled = true;
    document.body.classList.remove('curtain-off');
    vh = window.innerHeight;
  };

  const render = () => {
    if (!enabled) return;

    // Posun je zhora ohraničený výškou okna: po prvej obrazovke roletka
    // zmizne a dorovnávanie obsahu sa zastaví, takže stránka pokračuje
    // úplne normálnym scrollovaním.
    const y = Math.min(Math.max(window.scrollY, 0), vh);
    const rounded = Math.round(y);
    if (rounded === last) return;

    curtain.style.transform = `translate3d(0,${-rounded}px,0)`;
    /* Obsah je odsadený o obrazovku nadol, takže ho počas odchodu roletky
       ťaháme o toľko hore – stojí tým na mieste. Po odchode je dorovnanie
       nulové a transform zahadzujeme úplne: ponechaný by robil z obalu
       vzťažný rámec a prilepená mapa vnútri by prestala fungovať. */
    const offset = rounded - vh;
    below.style.transform = offset ? `translate3d(0,${offset}px,0)` : '';

    // GPU vrstvu držíme len počas samotného prechodu – potom je zbytočná.
    const active = rounded > 0 && rounded < vh;
    curtain.style.willChange = active ? 'transform' : '';
    below.style.willChange = active ? 'transform' : '';

    last = rounded;
  };

  const setup = () => {
    if (motionQuery.matches) { disable(); return; }
    measure();
    last = null;
    render();
  };

  setup();

  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { render(); ticking = false; });
  }, { passive: true });

  let resizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(setup, 150);
  });

  motionQuery.addEventListener('change', setup);
})();
