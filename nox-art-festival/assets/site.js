/* =========================================================================
   Telefóny kreslia stránku aj do pásu s hodinami a batériou. Aby tam siahala
   aj fixná hlavička (a nie len rolujúci sa obsah), musí mať stránka vo
   viewporte viewport-fit=cover. V šablóne to je, ale téma, optimalizačný
   plugin alebo keš vedia pridať vlastnú značku viewportu, ktorá tú našu
   prebije – preto to pre istotu dorovnáme aj tu, za behu.
   ========================================================================= */
(() => {
  const metas = document.querySelectorAll('meta[name="viewport"]');
  metas.forEach((meta) => {
    const obsah = meta.getAttribute('content') || '';
    if (/viewport-fit/.test(obsah)) return;
    meta.setAttribute('content', (obsah ? obsah + ', ' : '') + 'viewport-fit=cover');
  });
})();


/* Spodná hrana fixnej hlavičky v súradniciach okna. Zahŕňa všetko, čo nad
   obsahom stojí: samotnú hlavičku, bezpečnú zónu telefónu, presah hlavičky
   nad okraj okna aj admin lištu WordPressu, ak je fixovaná. Podľa tohto
   čísla zarovnávame skoky na sekcie. */
function vyskaHlavicky() {
  const header = document.querySelector('.site-header');
  if (!header) return 82;
  return Math.max(header.getBoundingClientRect().bottom, 0);
}

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
  const navigationLinks = document.querySelectorAll('.main-nav a[data-anchor], .main-nav a[href^="#"]:not(.nav-pill)');

  if ('IntersectionObserver' in window) {
    const navObserver = new IntersectionObserver((entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

      if (!visible) return;
      navigationLinks.forEach((link) => {
        const kotva = link.dataset.anchor || (link.getAttribute('href') || '').replace(/^.*#/, '');
        link.classList.toggle('is-active', kotva === visible.target.id);
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

  newsletterForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const cfg = window.NOX_SITE_TRACK || {};
    const data = new FormData(newsletterForm);
    const email = data.get('email');
    const stav = (text) => { if (formStatus) formStatus.textContent = text; };

    if (typeof email !== 'string' || !email.includes('@')) {
      stav('Skontroluj, prosím, e-mailovú adresu.');
      return;
    }
    if (!data.get('suhlas')) {
      stav('Pre prihlásenie potvrď súhlas so zasielaním noviniek.');
      return;
    }
    if (!cfg.newsletter || !cfg.ajaxUrl) {
      stav('Prihlásenie zatiaľ nie je dostupné. Skús to prosím neskôr.');
      return;
    }

    const tlacidlo = newsletterForm.querySelector('button[type="submit"]');
    if (tlacidlo) tlacidlo.disabled = true;
    stav('Odosielam…');
    data.append('action', 'nox_art_newsletter');

    try {
      const odpoved = await fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
      const vysledok = await odpoved.json().catch(() => null);
      stav(vysledok?.message || 'Prihlásenie sa nepodarilo. Skús to prosím neskôr.');
      if (vysledok?.ok) {
        newsletterForm.reset();
        window.noxTrack?.('generate_lead', { form: 'newsletter' });
      }
    } catch (chyba) {
      stav('Prihlásenie sa nepodarilo. Skontroluj pripojenie a skús to znova.');
    } finally {
      if (tlacidlo) tlacidlo.disabled = false;
    }
  });
})();


/* =========================================================================
   Meranie (Google Analytics 4) so súhlasom. Skript GA sa nenačíta, kým
   návštevník nepovolí meranie; voľba sa pamätá v prehliadači. Udalosti
   posielame cez window.noxTrack(názov, parametre) – bez súhlasu nerobí nič.
   ========================================================================= */
(() => {
  const cfg = window.NOX_SITE_TRACK || {};
  const KLUC = 'nox_consent';
  const banner = document.querySelector('[data-consent]');
  let povolene = false;
  let nacitane = false;

  const citaj = () => { try { return window.localStorage.getItem(KLUC); } catch (e) { return null; } };
  const zapis = (hodnota) => { try { window.localStorage.setItem(KLUC, hodnota); } catch (e) { /* bez pamäte sa lišta zobrazí znova */ } };

  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }

  const nacitaj = () => {
    if (nacitane || !cfg.gaId) return;
    nacitane = true;
    const skript = document.createElement('script');
    skript.async = true;
    skript.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(cfg.gaId)}`;
    document.head.appendChild(skript);

    gtag('js', new Date());
    gtag('consent', 'default', {
      analytics_storage: 'granted',
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
    });
    // Zobrazenia stránok posielame sami (aj pri zmene adresy bez načítania).
    gtag('config', cfg.gaId, { send_page_view: false });
    gtag('event', 'page_view', { page_location: window.location.href, page_path: window.location.pathname, page_title: document.title });
  };

  window.noxTrack = (nazov, parametre) => {
    if (!povolene || !cfg.gaId) return;
    gtag('event', nazov, parametre || {});
  };

  const zobraz = (viditelne) => { if (banner) banner.hidden = !viditelne; };

  if (cfg.gaId) {
    const ulozene = citaj();
    if (ulozene === 'granted') { povolene = true; nacitaj(); }
    else if (ulozene !== 'denied') zobraz(true);

    document.querySelector('[data-consent-accept]')?.addEventListener('click', () => {
      zapis('granted'); povolene = true; nacitaj(); zobraz(false);
    });
    document.querySelector('[data-consent-deny]')?.addEventListener('click', () => {
      zapis('denied'); povolene = false; zobraz(false);
    });
    document.addEventListener('click', (event) => {
      const otvor = event.target.closest('[data-consent-open]');
      if (!otvor) return;
      event.preventDefault();
      zobraz(true);
    });
  }

  // Kliky na sociálne siete a partnerov.
  document.addEventListener('click', (event) => {
    const odkaz = event.target.closest('a[href^="http"]');
    if (!odkaz) return;
    let hostitel = '';
    try { hostitel = new URL(odkaz.href).hostname; } catch (e) { return; }
    if (hostitel === window.location.hostname) return;
    if (odkaz.closest('.nav-social, .footer-social')) window.noxTrack('social_click', { network: hostitel });
    else if (odkaz.closest('.partners')) window.noxTrack('partner_click', { partner: hostitel });
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

  /* Mapbox GL je ~800 kB skriptu. Nesťahujeme ho pri načítaní stránky, ale až
     keď sa mapa blíži do zorného poľa – na mobile, kde je mapa až pod zoznamom
     diel, sa tak pri prvom vykreslení nestiahne vôbec. Filtre môžu medzitým
     poslať výber, preto si posledný zapamätáme a po spustení ho zopakujeme. */
  let poslednyFilter = null;
  const zapamatajFilter = (event) => { poslednyFilter = event.detail; };
  window.addEventListener('nox:map-filter', zapamatajFilter);

  const nacitajMapbox = () => new Promise((resolve, reject) => {
    if (typeof mapboxgl !== 'undefined') { resolve(); return; }

    // Mapu spúšťame až keď je tu skript aj jeho štýly – bez štýlov by sa
    // plátno vykreslilo v nesprávnej veľkosti.
    let hotovo = 0;
    const jeden = () => { hotovo += 1; if (hotovo === 2) resolve(); };

    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = config.mapboxCss || 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css';
    css.onload = jeden;
    css.onerror = jeden;   // aj bez štýlov radšej mapu skúsime ukázať
    document.head.appendChild(css);

    const script = document.createElement('script');
    script.src = config.mapboxJs || 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js';
    script.onload = jeden;
    script.onerror = reject;
    document.head.appendChild(script);
  });

  const spustitMapu = () => {
    window.removeEventListener('nox:map-filter', zapamatajFilter);
    vykreslitMapu();
    // Výber, ktorý prišiel skôr, než mapa vôbec existovala.
    if (poslednyFilter) window.dispatchEvent(new CustomEvent('nox:map-filter', { detail: poslednyFilter }));
  };

  let nacitavanie = null;
  const zacniNacitavat = () => {
    if (nacitavanie) return nacitavanie;
    nacitavanie = nacitajMapbox().then(spustitMapu).catch(() => {
      mapEl.innerHTML = '<div style="padding:20px;font-family:monospace;font-size:12px;color:#efeedc">Mapu sa nepodarilo načítať.</div>';
    });
    return nacitavanie;
  };

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      observer.disconnect();
      zacniNacitavat();
    }, { rootMargin: '400px' });
    observer.observe(mapEl);
  } else {
    zacniNacitavat();
  }

  /* Klik v zozname (dlaždica, odkaz na mapu) mapu potrebuje tiež – ak ešte
     nezačala sťahovať, spustíme to hneď. */
  document.addEventListener('click', (event) => {
    if (!event.target.closest?.('.gallery-tile, [data-show-on-map], .schedule-row')) return;
    zacniNacitavat();
  }, { capture: true });

  const vykreslitMapu = () => {
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

    const focusMiesto = (miestoId) => {
      const marker = markers[miestoId];
      if (!marker || !map) return;
      map.flyTo({ center: marker.getLngLat(), zoom: Math.max(map.getZoom(), 16), duration: 600 });
    };

    if (!config.token || typeof mapboxgl === 'undefined') {
      mapEl.innerHTML = '<div style="padding:20px;font-family:monospace;font-size:12px;color:#efeedc">Mapa nie je nastavená. V administrácii choď do NOX:ART &rsaquo; Nastavenia mapy a vlož Mapbox access token.</div>';
      return;
    }

    mapboxgl.accessToken = config.token;
    const pts = config.miesta.filter((m) => m.lat != null && m.lng != null);
    /* Miesta označené v administrácii ako "mimo výrezu" (napr. dielo na druhom
       konci mesta) sa do automatického výrezu nerátajú – inak by sa kvôli
       jedinému bodu oddialila celá mapa. Značku na mape majú ako ostatné. */
    const mimoVyrez = new Set(pts.filter((m) => m.mimoVyrez).map((m) => String(m.id)));
    const preVyrez = (zoznam) => {
      const vybrane = zoznam.filter((polozka) => !mimoVyrez.has(String(polozka.id)));
      return vybrane.length ? vybrane : zoznam;   // keď ostal len vzdialený bod, výrez patrí jemu
    };
    const center = pts.length ? [pts[0].lng, pts[0].lat] : [18.6045, 48.7715];

    map = new mapboxgl.Map({
      container: mapEl,
      style: config.style || 'mapbox://styles/mapbox/dark-v11',
      center,
      zoom: 14,
    });
    map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'bottom-right');
    map.on('click', () => zlozit());

    /* Keď Mapbox odmietne token alebo štýl, mapa ostane prázdna a človek
       nevie prečo – chybu preto vypíšeme priamo do rámčeka. */
    map.on('error', (event) => {
      const chyba = event?.error;
      const stav = chyba?.status ? ` (HTTP ${chyba.status})` : '';
      const popis = chyba?.message || 'neznáma chyba';
      let rada = '';
      if (chyba?.status === 401) rada = ' Skontroluj Mapbox access token v NOX:ART → Nastavenia.';
      if (chyba?.status === 403) rada = ' Token nemá práva na tento štýl, alebo je obmedzený na inú doménu.';
      if (chyba?.status === 404) rada = ' Štýl mapy neexistuje – skontroluj Style URL v nastaveniach.';

      let hlaska = mapEl.querySelector('[data-map-error]');
      if (!hlaska) {
        hlaska = document.createElement('div');
        hlaska.setAttribute('data-map-error', '');
        hlaska.style.cssText = 'position:absolute;inset:0;z-index:5;display:grid;place-items:center;padding:20px;text-align:center;font-family:monospace;font-size:12px;line-height:1.5;color:#efeedc;background:rgba(8,9,8,.82)';
        mapEl.appendChild(hlaska);
      }
      hlaska.textContent = `Mapa sa nenačítala${stav}: ${popis}.${rada}`;
      console.error('NOX:ART mapa', chyba || event);
    });

    // Mapa má pružnú výšku (dopĺňa zvyšné miesto v karte), takže pri zmene
    // veľkosti okna jej treba povedať, nech si prepočíta plátno – inak by
    // ostalo roztiahnuté v pôvodnom pomere a rozmazané.
    if ('ResizeObserver' in window) {
      new ResizeObserver(() => map.resize()).observe(mapEl);
    }
    map.once('load', () => map.resize());

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
          .addTo(map);
        markers[m.id] = marker;
        el.addEventListener('click', (event) => {
          event.stopPropagation();

          /* Značka otvorí detail tej položky, ktorú práve zastupuje – na jednom
             mieste môže stáť dielo aj bod programu. Keď ich je viac, najprv sa
             značka rozloží, nech sa dá vybrať konkrétna vec. */
          const polozky = vyberBodov[m.id] || [];
          if (polozky.length > 1) {
            rozlozit([m.lng, m.lat], polozky);
            return;
          }

          const first = dielaAt(m.id)[0];
          const id = polozky[0]?.id
            || (first && `work-${first.id}`)
            || `miesto-${m.id}`;
          otvorPolozku(id);
        });
      });

      const vychodzie = preVyrez(pts);
      if (vychodzie.length > 1) {
        const bounds = new mapboxgl.LngLatBounds();
        vychodzie.forEach((m) => bounds.extend([m.lng, m.lat]));
        map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 0 });
      } else if (vychodzie.length === 1) {
        map.setCenter([vychodzie[0].lng, vychodzie[0].lat]);
      }

      // Filtrovacia časť stihla výber poslať skôr, než mapa dokončila načítanie –
      // uplatníme ho až teraz, keď značky existujú. Výrez pritom nemeníme,
      // zostáva ten z fitBounds vyššie.
      pouziVyber(false);
    });

    /* Prejdenie kurzorom nad dlaždicou zvýrazní jej značku na mape – nie je
       na to treba klikať. Značku hľadáme až pri prejdení, lebo v čase, keď
       sa tieto poslucháče pripájajú, mapa ešte značky vytvorené nemá. */
    let predHoverom = null;

    const hoverMarker = (miestoId, on, tile) => {
      const marker = markers[miestoId];
      if (!marker) return;

      const el = marker.getElement();
      el.classList.toggle('is-hovered', on);

      /* Vzdialené miesto je síce na mape, ale mimo aktuálneho výrezu – pri
         prejdení kurzorom ho mape ukážeme a po odídení sa vráti pôvodný
         pohľad, nech sa výrez sám od seba nemení. */
      if (mimoVyrez.has(String(miestoId))) {
        if (on) {
          if (!predHoverom) predHoverom = { center: map.getCenter(), zoom: map.getZoom() };
          map.easeTo({ center: marker.getLngLat(), zoom: Math.max(map.getZoom(), 15), duration: 500 });
        } else if (predHoverom) {
          map.easeTo({ center: predHoverom.center, zoom: predHoverom.zoom, duration: 500 });
          predHoverom = null;
        }
      }

      /* Keď značka zastupuje viac vecí, ukazuje tri bodky – pri prejdení
         kurzorom nad dlaždicou v nej ukážeme číslo a farbu práve tej položky,
         nech je jasné, ktorá z nich to na mape je. */
      const polozky = vyberBodov[miestoId] || [];
      if (polozky.length < 2) return;

      const label = el.querySelector('b');
      if (!label) return;

      if (on) {
        if (tile?.dataset.cislo) label.textContent = tile.dataset.cislo;
        if (tile?.dataset.pin) el.style.setProperty('--pin', tile.dataset.pin);
      } else {
        label.textContent = '•••';
        if (polozky[0].pin) el.style.setProperty('--pin', polozky[0].pin);
      }
    };

    tiles.forEach((tile) => {
      const miestoId = tile.dataset.miesto;
      if (!miestoId) return;
      tile.addEventListener('pointerenter', () => hoverMarker(miestoId, true, tile));
      tile.addEventListener('pointerleave', () => hoverMarker(miestoId, false, tile));
    });

    /* Filtrovanie značiek podľa toho, čo je práve v zozname (udalosť posiela
       filtrovacia časť). Značky neodstraňujeme, len skrývame – znovuvytváranie
       pri každom prepnutí filtra by bolo zbytočne drahé. */
    let vyberMiest = null;
    let vyberBodov = {};

    /* Značka s číslom (alebo s tromi bodkami, keď na mieste stojí viac vecí). */
    const vytvorZnacku = (popis) => {
      const el = document.createElement('div');
      el.className = 'site-marker';
      el.appendChild(document.createElement('i'));
      const label = document.createElement('b');
      label.textContent = popis.cislo;
      el.appendChild(label);
      if (popis.pin) el.style.setProperty('--pin', popis.pin);
      return el;
    };

    const otvorPolozku = (id) => {
      const tile = document.getElementById(id);
      if (!tile) return;
      highlightTile(tile);
      tile.click();
    };

    /* Na jednom mieste môže stáť viac vecí – značka vtedy ukáže tri bodky a po
       kliknutí sa rozloží do samostatných značiek s vlastnými číslami. */
    let rozlozene = [];

    const zlozit = () => {
      rozlozene.forEach((marker) => marker.remove());
      rozlozene = [];
    };

    const rozlozit = (lngLat, polozky) => {
      zlozit();
      const polomer = 52;
      polozky.forEach((popis, i) => {
        const uhol = (-90 + (360 / polozky.length) * i) * (Math.PI / 180);
        const el = vytvorZnacku(popis);
        el.classList.add('is-expanded');
        el.addEventListener('click', (event) => {
          event.stopPropagation();
          otvorPolozku(popis.id);
          zlozit();
        });
        rozlozene.push(
          new mapboxgl.Marker({
            element: el,
            anchor: 'bottom',
            offset: [Math.cos(uhol) * polomer, Math.sin(uhol) * polomer],
          }).setLngLat(lngLat).addTo(map)
        );
      });
    };

    const pouziVyber = (prisposobVyrez) => {
      if (!vyberMiest) return;
      const miesta = vyberMiest;

      const viditelne = [];
      Object.keys(markers).forEach((id) => {
        const show = !miesta.length || miesta.includes(String(id));
        const el = markers[id].getElement();
        el.classList.toggle('is-map-hidden', !show);
        if (!show) return;
        viditelne.push({ id: String(id), marker: markers[id] });

        // Číslo a farba podľa položiek, ktoré značku do výberu dostali.
        const polozky = vyberBodov[id] || [];
        if (!polozky.length) return;
        const label = el.querySelector('b');
        const zhluk = polozky.length > 1;
        el.classList.toggle('is-cluster', zhluk);
        if (label) label.textContent = zhluk ? '•••' : polozky[0].cislo;
        if (polozky[0].pin) el.style.setProperty('--pin', polozky[0].pin);
      });

      if (!map || !prisposobVyrez || !viditelne.length) return;

      // Výrez prispôsobíme tomu, čo zostalo – pri jedinom bode naň priblížime.
      const vyrez = preVyrez(viditelne);
      if (vyrez.length === 1) {
        map.flyTo({ center: vyrez[0].marker.getLngLat(), zoom: Math.max(map.getZoom(), 16), duration: 600 });
        return;
      }
      const bounds = new mapboxgl.LngLatBounds();
      vyrez.forEach((polozka) => bounds.extend(polozka.marker.getLngLat()));
      map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 600 });
    };

    window.addEventListener('nox:map-filter', (event) => {
      const miesta = event.detail?.miesta || [];
      const rovnaky = vyberMiest && vyberMiest.join(',') === miesta.join(',');
      vyberMiest = miesta;
      vyberBodov = event.detail?.body || {};
      zlozit();
      pouziVyber(!rovnaky);
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
  };
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
    /* Pohľady sekcie: dlaždice ("items") a časový harmonogram. Hľadáme ich
       podľa data-view-panel, nie data-view – to nesie aj samotné tlačidlo,
       ktoré pohľad prepína, a skrývalo by sa potom samo. */
    const views = [...document.querySelectorAll('[data-view-panel]')];

    // Hlavná skupina (Diela / Sprievodný program / Podniky) a prípadné
    // spresnenie v jej druhom rade. Položky nesú aj nadradené kategórie,
    // takže na hlavnú skupinu sadne všetko, čo pod ňu patrí.
    const details = [...document.querySelectorAll('[data-detail]')];
    const backButton = document.querySelector('[data-detail-back]');
    // V detaile ustúpi aj hlavička sekcie – zostane mapa, popis a návrat.
    const section = target.closest('.section');

    let parent = chips.find((chip) => chip.classList.contains('is-active'))?.dataset.filter || '';
    let child = '';
    let view = 'items';
    // Odkiaľ sa do detailu prišlo – tlačidlo späť vráti ten istý pohľad
    // aj miesto v zozname, kde človek pred otvorením bol.
    let viewBeforeDetail = 'items';

    /* ---------------------------------------------------------------------
       Adresa podstránky. Každý pohľad má vlastnú cestu – /diela,
       /sprievodny-program, /harmonogram, a pri otvorenom detaile
       /diela/the-moon. Server tieto adresy načíta ako tú istú stránku
       (includes/routing.php) a tu z nich len poskladáme stav.
       ------------------------------------------------------------------ */
    const route = window.NOX_SITE_ROUTE;
    // Z adresy stránky potrebujeme len cestu – porovnávame ju s location.
    const zakladCesty = route ? new URL(route.base, window.location.href).pathname : '';
    let adresaPripravena = false;

    const cestaStavu = () => {
      if (!route) return '';
      if (view !== 'items' && view !== 'detail') return view === 'harmonogram' ? (route.harmonogram || 'harmonogram') : view;

      let skupina = child || parent;
      if (view === 'detail') {
        const open = details.find((el) => !el.hidden);
        if (open?.dataset.detail.startsWith('autor-')) skupina = 'autori';
        const slug = open?.dataset.slug;
        return slug ? `${skupina}/${slug}` : skupina;
      }
      return skupina;
    };

    let potlacAdresu = false;

    function aktualizujAdresu() {
      if (!route || !window.history?.replaceState || potlacAdresu) return;
      // Kým človek nič neprepol, adresu nechávame tak, ako prišiel.
      if (!adresaPripravena && !route.view) return;

      const cesta = cestaStavu();
      const url = (cesta ? zakladCesty + cesta + '/' : zakladCesty) + window.location.search;
      if (url === window.location.pathname + window.location.search) return;

      // Prvé nastavenie len opraví adresu, ďalšie pridávajú krok do histórie,
      // nech funguje tlačidlo Späť v prehliadači.
      if (adresaPripravena) {
        window.history.pushState({ nox: cesta }, '', url);
        window.noxTrack?.('page_view', { page_location: window.location.href, page_path: window.location.pathname, page_title: document.title });
      }
      else window.history.replaceState({ nox: cesta }, '', url);
    }

    /* Nastaví zoznam podľa adresy – klikaním na tlačidlá, nech sú filtre,
       zvýraznenie aj mapa v súlade. */
    const pouziCestu = (pohlad, polozka) => {
      const pohladChip = chips.find((chip) => chip.dataset.view === pohlad);
      if (pohladChip) {
        pohladChip.click();
        if (!polozka) return;
      }

      if (pohlad && !pohladChip) {
        const subChip = [...document.querySelectorAll('[data-filter-parent] .filter-chip')]
          .find((chip) => chip.dataset.filter === pohlad);
        if (subChip) {
          const parentSlug = subChip.closest('[data-filter-sub]')?.dataset.filterSub;
          (chips.find((chip) => chip.dataset.filter === parentSlug) || chips[0])?.click();
          subChip.click();
        } else {
          (chips.find((chip) => chip.dataset.filter === pohlad) || chips[0])?.click();
        }
      }

      if (!polozka) return;
      const zhody = details.filter((el) => el.dataset.slug === polozka);
      const jeAutor = (el) => el.dataset.detail.startsWith('autor-');
      const detail = zhody.find((el) => jeAutor(el) === (pohlad === 'autori')) || zhody[0];
      if (detail) openDetail(detail.dataset.detail);
    };

    /* Mapa ukazuje len to, čo je práve v zozname: pri skupine jej položky,
       v detaile jediný bod, v harmonograme všetko, čo má čas. Zoznam miest
       posielame mape udalosťou, aby o sebe tie dve časti nemuseli vedieť. */
    /* Počítadlo nad mapou: koľko položiek je práve vo výbere a v akom tvare
       ("20 diel", "10 podujatí", "6 inštalácií"). Tvary slov podľa kategórie
       posiela server v NOX_SITE_MAP.pocty. */
    const countEl = document.querySelector('[data-map-count]');
    const tvarSlova = (pocet, tvary) => {
      if (pocet === 1) return tvary[0];
      if (pocet >= 2 && pocet <= 4) return tvary[1];
      return tvary[2];
    };

    const updateCount = (tiles) => {
      if (!countEl) return;
      const tvary = (window.NOX_SITE_MAP || {}).pocty || {};
      const kluc = view === 'harmonogram' ? '_harmonogram' : (view === 'autori' ? '_autori' : (child || parent));
      const tvar = tvary[kluc] || tvary._default || ['položka', 'položky', 'položiek'];
      // Pri autoroch počítame autorov, nie diela na mape.
      const pocet = view === 'autori' ? document.querySelectorAll('[data-autor]').length : tiles.length;
      countEl.textContent = `${pocet} ${tvarSlova(pocet, tvar)} / Sídlisko Píly`;
    };

    const syncMap = () => {
      let tiles = [];
      if (view === 'detail') {
        const open = details.find((el) => !el.hidden);
        const tile = open && document.getElementById(open.dataset.detail);
        // Detail autora ukáže na mape všetky jeho diela.
        const autorDiela = (open?.dataset.autorDiela || '').split(' ').filter(Boolean);
        tiles = autorDiela.length
          ? autorDiela.map((id) => document.getElementById(id)).filter(Boolean)
          : (tile ? [tile] : []);
      } else if (view === 'harmonogram') {
        tiles = [...target.querySelectorAll('.gallery-tile[data-cas]')];
      } else if (view === 'autori') {
        tiles = [...target.querySelectorAll('.gallery-tile[data-work]')];
      } else {
        tiles = [...target.querySelectorAll('.gallery-tile')].filter((tile) => !tile.classList.contains('is-filtered-out'));
      }

      /* Značke posielame aj číslo a farbu položky, ktorá ju do výberu
         dostala. Na jednom mieste môže stáť dielo aj bod programu – bez toho
         by značka pri sprievodnom programe ukazovala číslo a farbu diela. */
      const body = new Map();
      tiles.forEach((tile) => {
        const miesto = tile.dataset.miesto;
        if (!miesto) return;
        if (!body.has(miesto)) body.set(miesto, []);
        body.get(miesto).push({ id: tile.id, cislo: tile.dataset.cislo || '', pin: tile.dataset.pin || '' });
      });

      updateCount(tiles);

      window.dispatchEvent(new CustomEvent('nox:map-filter', {
        detail: { miesta: [...body.keys()], body: Object.fromEntries(body) },
      }));
    };

    const apply = () => {
      views.forEach((el) => { el.hidden = el.dataset.viewPanel !== view; });
      bar.hidden = view === 'detail';
      section?.classList.toggle('is-detail', view === 'detail');

      if (view !== 'items') {
        if (emptyNote) emptyNote.hidden = true;
        syncMap();
        // Pohľady (harmonogram, autori) aj detail položky zapíšu svoju adresu.
        aktualizujAdresu();
        return;
      }

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

      syncMap();
      aktualizujAdresu();
    };

    const syncSubBars = () => {
      subBars.forEach((sub) => {
        // V harmonograme sa nefiltruje podľa podkategórií – druhý rad je preč.
        const mine = view === 'items' && sub.dataset.filterSub === parent;
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
      chip.addEventListener('click', (event) => {
        chips.forEach((other) => {
          const active = other === chip;
          other.classList.toggle('is-active', active);
          other.setAttribute('aria-pressed', String(active));
        });
        view = chip.dataset.view || 'items';
        parent = chip.dataset.filter || parent;
        if (event.isTrusted) window.noxTrack?.('tab_view', { tab: chip.dataset.view || chip.dataset.filter || 'items' });
        child = '';
        syncSubBars();
        apply();
        // Po kliknutí človekom zarovnáme filtre pod hlavičku, nech je zoznam
        // hneď po ruke (programové kliknutie z adresy to robí samo).
        if (event.isTrusted) requestAnimationFrame(() => requestAnimationFrame(scrollToFilters));
      });
    });

    /* Posun na viditeľnú hranu sekcie. Prehliadačov skok na kotvu sa riadi
       hodnotou scroll-margin-top z CSS, ktorá nevie o admin lište ani o tom,
       že vrch tmavej sekcie je zasunutý pod hero – počítame si to teda sami,
       nech zoznam aj detail končia na tom istom mieste. */
    const scrollToSection = () => {
      if (!section) return;
      const prekryv = parseFloat(getComputedStyle(section).marginTop) || 0;   // záporný
      const top = section.getBoundingClientRect().top + window.scrollY - prekryv - vyskaHlavicky();
      window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
    };

    /* Zarovnanie filtrovacieho pruhu tesne pod hlavičku – tak je zoznam hneď
       použiteľný, bez nadpisu nad ním. Polohu pruhu čítame z rozloženia
       (offsetTop), nie z getBoundingClientRect: pruh má pri načítaní
       "vyjazdenie" (transform), ktoré by výsledok o desiatky pixelov
       posunulo – raz áno, raz nie, podľa toho, či sa už zobrazil. */
    const hornaHranaPruhu = () => {
      let y = 0;
      for (let el = bar; el; el = el.offsetParent) y += el.offsetTop;
      return y;
    };
    const filtreY = () => Math.max(hornaHranaPruhu() - vyskaHlavicky() - 32, 0);
    const dorovnajFiltre = () => {
      const chyba = bar.getBoundingClientRect().top - (vyskaHlavicky() + 32);
      if (Math.abs(chyba) > 3) window.scrollBy({ top: chyba, behavior: 'instant' });
    };

    /* Vlastný plynulý posun. Vstavaný (behavior: 'smooth') si cieľ spočíta
       raz na začiatku, ale zoznam sa pri prepnutí záložky skracuje či
       predlžuje a stránka sa pod ním ešte dorovnáva – posun by skončil vedľa
       a až dodatočná oprava by ho zarovnala. Tu sa cieľ počíta v každom
       snímku, takže posun skončí presne na mieste. Zastaví ho aj kolieskom
       alebo dotykom. */
    let posunBezi = 0;
    const scrollToFilters = () => {
      const id = ++posunBezi;
      const start = window.scrollY;
      const zaciatok = performance.now();
      const trvanie = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 520;
      const zastav = () => { posunBezi++; };
      window.addEventListener('wheel', zastav, { once: true, passive: true });
      window.addEventListener('touchstart', zastav, { once: true, passive: true });

      const krok = (teraz) => {
        if (id !== posunBezi) return;
        const p = trvanie ? Math.min((teraz - zaciatok) / trvanie, 1) : 1;
        const plynule = 1 - Math.pow(1 - p, 3);
        const ciel = filtreY();
        // 'instant': stránka má v CSS scroll-behavior: smooth, ktoré by každý
        // snímok animovalo samo a bojovalo by s touto animáciou.
        window.scrollTo({ top: start + (ciel - start) * plynule, behavior: 'instant' });
        if (p < 1) { requestAnimationFrame(krok); return; }
        window.removeEventListener('wheel', zastav);
        window.removeEventListener('touchstart', zastav);
        // Obrázky a mapa ešte chvíľu menia výšku stránky – polohu preto po
        // doznení ešte pár krát skontrolujeme.
        [0, 150, 400, 900].forEach((ms) => setTimeout(() => { if (id === posunBezi) dorovnajFiltre(); }, ms));
      };
      requestAnimationFrame(krok);
    };

    /* Detail položky. Je vykreslený na serveri pri každej dlaždici, takže sa
       len prepína viditeľnosť – otvorenie je okamžité. */
    const openDetail = (id) => {
      const detail = details.find((el) => el.dataset.detail === id);
      if (!detail) return;
      details.forEach((el) => { el.hidden = el !== detail; });
      if (view !== 'detail') viewBeforeDetail = view;
      window.noxTrack?.('select_content', {
        content_type: id.startsWith('autor-') ? 'autor' : 'polozka',
        item_id: detail.dataset.slug || id,
      });
      view = 'detail';
      syncSubBars();
      apply();

      /* Detail sa otvára aj z dlaždice hlboko v zozname, takže stránku
         posunieme na začiatok sekcie – inak by človek pozeral na prázdne
         miesto pod textom a tlačidlo späť by mal nad obrazovkou. */
      scrollToSection();
    };

    // Dlaždice autorov otvárajú detail autora.
    document.querySelectorAll('[data-open]').forEach((tile) => {
      const otvor = () => openDetail(tile.dataset.open);
      tile.addEventListener('click', (event) => { event.preventDefault(); otvor(); });
      tile.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); otvor(); }
      });
    });

    // Klikacia je celá dlaždica, nielen odkaz na mapu – položka bez miesta
    // odkaz nemá, a detail má mať každá.
    target.querySelectorAll('.gallery-tile[id]').forEach((tile) => {
      tile.addEventListener('click', (event) => {
        event.preventDefault();
        openDetail(tile.id);
      });
    });

    /* Odkaz v menu na túto sekciu znamená "ukáž mi celý zoznam" – ak je
       otvorený detail, zavrieme ho. Bez toho by kliknutie na "Program a
       diela" len odscrollovalo na detail, v ktorom človek už je. */
    /* Riadky v harmonograme sa správajú ako dlaždice: jednotlivá položka
       otvorí svoj detail, zlúčený riadok prepne zoznam na svoju kategóriu. */
    document.querySelectorAll('[data-row-detail], [data-row-kategoria]').forEach((row) => {
      row.addEventListener('click', () => {
        const id = row.dataset.rowDetail;
        if (id) { openDetail(id); return; }

        const slug = row.dataset.rowKategoria;
        if (!slug) return;

        /* Prepnutie robíme vždy kliknutím na niektoré tlačidlo, nie ručným
           prepísaním stavu – zoznam, filtre aj zvýraznené tlačidlo tak
           zostanú v súlade. Keď kategória vlastné tlačidlo nemá, vraciame
           sa na prvú skupinu. */
        const subChip = [...document.querySelectorAll('[data-filter-parent] .filter-chip')]
          .find((chip) => chip.dataset.filter === slug);

        if (subChip) {
          const parentSlug = subChip.closest('[data-filter-sub]')?.dataset.filterSub;
          (chips.find((chip) => chip.dataset.filter === parentSlug) || chips[0])?.click();
          subChip.click();
          return;
        }

        (chips.find((chip) => chip.dataset.filter === slug) || chips[0])?.click();
      });
    });

    document.querySelectorAll('.main-nav a[data-anchor], .main-nav a[href*="#"], .footer-links a[data-anchor], .footer-links a[href*="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        const hash = link.dataset.anchor || (link.getAttribute('href') || '').split('#')[1];
        if (!section || hash !== section.id || view !== 'detail') return;
        view = 'items';
        syncSubBars();
        // Adresu pri skoku z menu zapíše handler menu (/program/), nie filter.
        potlacAdresu = true;
        apply();
        potlacAdresu = false;
      });
    });

    backButton?.addEventListener('click', () => {
      view = viewBeforeDetail;
      syncSubBars();
      apply();

      /* Po návrate zarovnáme filtre pod hlavičku, nech je zoznam hneď po ruke
         – nadpis sekcie v tej chvíli nemá čo povedať. Až po prekreslení:
         zoznam je oveľa vyšší než detail a prehliadač si po zmene výšky
         polohu sám dorovnáva. */
      requestAnimationFrame(() => {
        requestAnimationFrame(scrollToFilters);
      });
    });

    subBars.forEach((sub) => {
      const subChips = [...sub.querySelectorAll('.filter-chip')];
      subChips.forEach((chip) => {
        chip.addEventListener('click', (event) => {
          // Druhé kliknutie na to isté spresnenie ho zruší a vráti celú skupinu.
          const turnOff = chip.classList.contains('is-active');
          subChips.forEach((other) => {
            const active = !turnOff && other === chip;
            other.classList.toggle('is-active', active);
            other.setAttribute('aria-pressed', String(active));
          });
          child = turnOff ? '' : (chip.dataset.filter || '');
          apply();
          if (event.isTrusted) requestAnimationFrame(() => requestAnimationFrame(scrollToFilters));
        });
      });
    });

    syncSubBars();
    apply();

    if (route) {
      if (route.view) pouziCestu(route.view, route.item);
      aktualizujAdresu();
      adresaPripravena = true;

      // Adresa sekcie (/program, /o-festivale, /partneri) alebo pohľadu
      // (/diela, /podniky …) má otvoriť príslušnú sekciu, nie začiatok stránky.
      const cielovaKotva = route.section ? (route.sections || {})[route.section] : (route.view ? 'program' : '');
      const cielovaSekcia = cielovaKotva ? document.getElementById(cielovaKotva) : null;
      if (cielovaSekcia) {
        // Adresa pohľadu (/diela, /autori, /harmonogram) zarovná filtre pod
        // hlavičku – rovnako ako návrat zo detailu; ostatné adresy (sekcie,
        // detail) začínajú na hrane sekcie.
        const naFiltre = route.view && !route.item && !route.section;
        const posun = () => {
          const prekryv = parseFloat(getComputedStyle(cielovaSekcia).marginTop) || 0;
          const y = naFiltre
            ? filtreY()
            : cielovaSekcia.getBoundingClientRect().top + window.scrollY - prekryv - vyskaHlavicky();
          window.scrollTo({ top: Math.max(0, y), behavior: 'instant' });
        };
        posun();
        // Obrázky a písmo sa dotiahnu neskôr a posunú rozloženie.
        window.addEventListener('load', () => setTimeout(posun, 80), { once: true });
        if (naFiltre) setTimeout(dorovnajFiltre, 600);
      }

      // Späť/dopredu v prehliadači: cestu prečítame z adresy.
      window.addEventListener('popstate', () => {
        const zvysok = window.location.pathname.replace(zakladCesty, '');
        const [pohlad, polozka] = zvysok.split('/').filter(Boolean);
        const kotvaSekcie = pohlad && (route.sections || {})[pohlad];
        if (kotvaSekcie || !pohlad) {
          // Adresa sekcie len posunie stránku, filter ani detail nemení.
          const cielSekcie = kotvaSekcie ? document.getElementById(kotvaSekcie) : null;
          const prekryv = cielSekcie ? (parseFloat(getComputedStyle(cielSekcie).marginTop) || 0) : 0;
          const y = cielSekcie ? cielSekcie.getBoundingClientRect().top + window.scrollY - prekryv - vyskaHlavicky() : 0;
          window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
          return;
        }
        adresaPripravena = false;
        if (!pohlad) chips[0]?.click();
        else pouziCestu(pohlad, polozka);
        adresaPripravena = true;
      });
    }
  });
})();





/* =========================================================================
   Cudzie pruhy nad hlavičkou. Téma alebo iný plugin vie vložiť oznamovaciu
   lištu hneď za <body> (nielen cez wp_body_open, aj skriptom po načítaní).
   Festivalová stránka má vlastnú hlavičku, takže všetko, čo nie je naše
   a stojí nad ňou, schováme. Prvky na konci stránky (cookie lišta, modály)
   sa nedotýkame.
   ========================================================================= */
(() => {
  if (!document.body.classList.contains('nox-art-site')) return;

  const NASE = '.site-header, main, .site-footer, .scroll-progress, .newsletter-dock, #wpadminbar';
  const PRESKOCIT = ['SCRIPT', 'STYLE', 'LINK', 'META', 'NOSCRIPT', 'TEMPLATE'];

  const upratat = () => {
    const header = document.querySelector('.site-header');
    if (!header) return;
    for (const el of [...document.body.children]) {
      if (el === header) break;              // ďalej už je naša stránka
      if (PRESKOCIT.includes(el.tagName)) continue;
      if (el.matches(NASE) || el.contains(header)) continue;
      el.hidden = true;
      el.style.display = 'none';
    }
  };

  upratat();
  window.addEventListener('load', upratat);
  // Lišta sa vie vložiť aj neskôr skriptom – chvíľu to sledujeme.
  if ('MutationObserver' in window) {
    const observer = new MutationObserver(upratat);
    observer.observe(document.body, { childList: true });
    setTimeout(() => observer.disconnect(), 10000);
  }
})();


/* =========================================================================
   Skoky z menu na sekcie. Prehliadač si posun na kotvu počíta z hodnoty
   scroll-margin-top v CSS, ktorá nevie o dvoch veciach: o admin lište
   WordPressu (tá posúva fixnú hlavičku nižšie) a o tom, že niektoré sekcie
   sú záporným odstupom zasunuté pod predchádzajúcu. Pod menu tak zostával
   pruh predošlej sekcie. Počítame to preto sami, pre všetky sekcie rovnako.
   ========================================================================= */
(() => {
  const links = [...document.querySelectorAll('.main-nav a[data-anchor], .main-nav a[href*="#"], .footer-links a[data-anchor], .footer-links a[href*="#"]')];
  if (!links.length) return;

  const scrollToSection = (section) => {
    // Zasunutie pod predchádzajúcu sekciu je záporný odstup – odpočítaním
    // záporného čísla sa cieľ posunie práve o toľko.
    const prekryv = parseFloat(getComputedStyle(section).marginTop) || 0;
    const top = section.getBoundingClientRect().top + window.scrollY - prekryv - vyskaHlavicky();
    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
  };

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      const hash = link.dataset.anchor || (link.getAttribute('href') || '').split('#')[1];
      if (!hash) return;
      const section = document.getElementById(hash);
      if (!section) return;   // odkaz vedie na inú podstránku
      // Newsletter je vysúvací panel, nie sekcia v toku – ten sa otvára,
      // nescrolluje sa naň.
      if (section.hasAttribute('data-newsletter-dock')) return;
      event.preventDefault();
      scrollToSection(section);

      // Odkaz s vlastnou adresou (/program/) ju po kliknutí zapíše do
      // adresného riadka, nech sa dá skopírovať a zdieľať.
      if (link.hasAttribute('data-podstranka') && window.history?.pushState) {
        const ciel = new URL(link.href, window.location.href);
        if (ciel.pathname !== window.location.pathname) {
          window.history.pushState({ nox: ciel.pathname }, '', ciel.pathname + window.location.search);
          window.noxTrack?.('page_view', { page_location: window.location.href, page_path: window.location.pathname, page_title: document.title });
        }
      }
    });
  });
})();


/* =========================================================================
   Vysúvací newsletter. Na stránke nie je vidieť nič; okno sa vysunie sprava
   po kliknutí na "Sleduj nás" (akýkoľvek odkaz na #kontakt) a zatvára sa
   krížikom, klávesom Esc alebo kliknutím mimo panela.
   ========================================================================= */
(() => {
  const dock = document.querySelector('[data-newsletter-dock]');
  if (!dock) return;

  const panel = dock.querySelector('.newsletter-panel');
  // Odkazy "Sleduj nás" a kotvy na #kontakt panel otvárajú – fixovaný panel
  // nie je kam odscrollovať, takže skok na kotvu nahrádzame vysunutím.
  const spuste = [...document.querySelectorAll('a[href*="#kontakt"]')].filter((link) => {
    const url = new URL(link.href, window.location.href);
    return url.pathname === window.location.pathname;   // inak odkaz vedie na inú podstránku
  });
  let poslednySpust = null;
  let scrollPriOtvoreni = 0;

  const nastav = (otvorene) => {
    scrollPriOtvoreni = window.scrollY;
    dock.classList.toggle('is-open', otvorene);
    spuste.forEach((link) => link.setAttribute('aria-expanded', otvorene ? 'true' : 'false'));
    if (otvorene) panel?.querySelector('input')?.focus({ preventScroll: true });
  };

  spuste.forEach((link) => {
    link.setAttribute('aria-controls', 'newsletter-panel');
    link.setAttribute('aria-expanded', 'false');
    link.addEventListener('click', (event) => {
      event.preventDefault();
      // Druhý klik na ten istý odkaz panel zase zasunie.
      const otvorene = dock.classList.contains('is-open');
      poslednySpust = link;
      nastav(!otvorene);
      if (otvorene) link.focus({ preventScroll: true });
    });
  });

  dock.querySelector('[data-newsletter-close]')?.addEventListener('click', () => {
    nastav(false);
    poslednySpust?.focus({ preventScroll: true });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && dock.classList.contains('is-open')) nastav(false);
  });

  document.addEventListener('click', (event) => {
    if (!dock.classList.contains('is-open')) return;
    if (dock.contains(event.target)) return;
    // Klik na odkaz, ktorý panel práve otvoril, ho nesmie hneď zavrieť.
    if (event.target.closest?.('a[href*="#kontakt"]')) return;
    nastav(false);
  });

  /* Rolovanie stránky panel zasunie – ide o prekryv nad obsahom, nie o jeho
     časť. Malý posun (napr. dorovnanie kotvy) ešte neráta. */
  window.addEventListener('scroll', () => {
    if (!dock.classList.contains('is-open')) return;
    if (Math.abs(window.scrollY - scrollPriOtvoreni) < 40) return;
    nastav(false);
  }, { passive: true });

  if (window.location.hash === '#kontakt') nastav(true);
})();
