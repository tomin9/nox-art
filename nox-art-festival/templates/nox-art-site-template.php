<?php
/**
 * Celostránková šablóna NOX:ART. Vykresľuje kompletnú stránku bez
 * hlavičky/päty aktívnej témy (viď includes/site-template.php).
 */
if (!defined('ABSPATH')) exit;

$diela = nox_art_data_diela();
$miesta = nox_art_data_miesta();
$umelci = nox_art_data_umelci();
$programByDay = nox_art_site_program_by_day();
$visualClasses = ['visual-one', 'visual-two', 'visual-three', 'visual-four', 'visual-five', 'visual-six', 'visual-seven', 'visual-eight'];
$dielaCount = count($diela);
$umelecById = [];
foreach ($umelci as $u) $umelecById[$u['id']] = $u;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="NOX:ART — medzinárodný festival súčasného umenia na sídlisku Píly v Prievidzi, 30.–31. októbra 2026.">
<meta name="theme-color" content="#efeedc">
<title><?php echo esc_html(get_the_title() ?: 'NOX:ART — Sídlisko Píly, Prievidza'); ?></title>
<link rel="icon" href="<?php echo nox_art_site_asset('favicon.svg'); ?>" type="image/svg+xml">
<?php wp_head(); ?>
</head>
<body <?php body_class('nox-art-site'); ?>>
<?php wp_body_open(); ?>
<div class="scroll-progress" aria-hidden="true"><span></span></div>

<header class="site-header" data-header>
  <a class="brand" href="#top" aria-label="NOX:ART a Ars Preuge — späť na začiatok">
    <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="NOX:ART">
    <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" alt="Ars Preuge">
  </a>
  <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Otvoriť menu">
    <span></span><span></span>
  </button>
  <nav class="main-nav" id="main-nav" aria-label="Hlavná navigácia">
    <a href="#festival">O festivale</a>
    <a href="#program">Program</a>
    <a href="#instalacie">Diela</a>
    <a href="#info">Mapa + info</a>
    <a href="#partneri">Partneri</a>
    <a class="nav-pill" href="#kontakt">Sleduj nás <span aria-hidden="true">↗</span></a>
  </nav>
</header>

<main>
<div class="stack-group" data-stack-group style="--panels:7">
<section class="hero stack-panel" id="top">
<div class="stack-inner">
  <div class="hero-copy reveal">
    <h1 class="sr-only">NOX:ART — Medzinárodný festival súčasného umenia</h1>
    <p class="hero-location-title">Sídlisko Píly</p>
    <div class="hero-meta">
      <p class="hero-date"><strong>30.&ndash;31.</strong><br>október<br><span>&rsquo;26</span></p>
    </div>
    <p class="hero-intro">Keď sa zotmie, Prievidza sa rozsvieti umením. NOX:ART premieňa verejný priestor na galériu svetla, obrazu, zvuku a zážitkov.</p>
    <div class="hero-actions">
      <a class="button button-dark" href="#program">Program <span aria-hidden="true">↗</span></a>
      <a class="button button-outline" href="#festival">O festivale</a>
    </div>
  </div>
  <div class="hero-mark" aria-hidden="true" data-parallax>
    <img src="<?php echo nox_art_site_asset('ars-preuge-ap.png'); ?>" alt="" aria-hidden="true">
  </div>
  <p class="hero-side-note">Umenie<br>vo verejnom<br>priestore</p>
  <a class="hero-scroll" href="#festival" aria-label="Posunúť sa nižšie">
    <span>#NOXART26</span><b aria-hidden="true">↓</b>
  </a>
</div>
</section>

<section class="section about stack-panel" id="festival" aria-labelledby="festival-title">
<div class="stack-inner">
  <div class="section-label reveal"><span>01</span> O festivale</div>
  <div class="about-grid">
    <div class="about-heading reveal">
      <h2 id="festival-title">Sídlisko sa na dve noci zmení na otvorenú galériu.</h2>
    </div>
    <div class="about-copy reveal">
      <p>NOX:ART prináša súčasné umenie priamo medzi domy, ulice a sgrafitá sídliska Píly. Svetelné objekty, projekcie, zvukové zásahy a digitálne diela vytvoria trasu, ktorú možno objavovať vlastným tempom.</p>
      <p>Festival spája pamäť miesta so súčasnou tvorbou a ukazuje, že kvalitné umenie nemusí zostať zatvorené v galérii.</p>
      <a class="text-link" href="#info">Ako sa dostať na festival <span aria-hidden="true">↗</span></a>
    </div>
  </div>
  <div class="festival-facts reveal" aria-label="Základné informácie o festivale">
    <article><strong>02</strong><span>festivalové<br>večery</span></article>
    <article><strong>PÍLY</strong><span>galéria pod<br>otvoreným nebom</span></article>
    <article><strong>&asymp;<?php echo (int) $dielaCount; ?></strong><span>diel na jednej<br>festivalovej trase</span></article>
    <article><strong>&rsquo;26</strong><span>Prievidza<br>30.&ndash;31. október</span></article>
  </div>
  <div class="marquee" aria-hidden="true">
    <div class="marquee-track">
      <span>SVETLO — OBRAZ — ZVUK — PRIESTOR — NOX:ART — </span>
      <span>SVETLO — OBRAZ — ZVUK — PRIESTOR — NOX:ART — </span>
    </div>
  </div>
</div>
</section>

<section class="section program stack-panel" id="program" aria-labelledby="program-title">
<div class="stack-inner">
  <div class="section-label section-label-light reveal"><span>02</span> Program</div>
  <div class="program-head reveal">
    <h2 id="program-title">Dve noci.<br>Jedna svetelná trasa.</h2>
    <p>Program budeme odhaľovať postupne. Finálny harmonogram, miesta a mená autorov zverejníme pred festivalom.</p>
  </div>
  <?php if ($programByDay): ?>
  <div class="program-tabs reveal" role="tablist" aria-label="Festivalové dni">
    <?php $i = 0; foreach ($programByDay as $datum => $items): list($dayName, $dayDate) = nox_art_site_day_label($datum); $panelId = 'day-' . sanitize_title($datum); ?>
    <button class="program-tab<?php echo $i === 0 ? ' is-active' : ''; ?>" role="tab" type="button" data-tab="<?php echo esc_attr($panelId); ?>" id="tab-<?php echo esc_attr($panelId); ?>" aria-controls="<?php echo esc_attr($panelId); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
      <span><?php echo esc_html($dayName); ?></span><strong><?php echo esc_html($dayDate); ?></strong>
    </button>
    <?php $i++; endforeach; ?>
  </div>
  <div class="program-panels reveal">
    <?php $i = 0; foreach ($programByDay as $datum => $items): $panelId = 'day-' . sanitize_title($datum); ?>
    <div class="program-panel<?php echo $i === 0 ? ' is-active' : ''; ?>" id="<?php echo esc_attr($panelId); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr($panelId); ?>"<?php echo $i === 0 ? '' : ' hidden'; ?>>
      <?php foreach ($items as $ri => $item): ?>
      <article class="program-row">
        <time><?php echo esc_html($item['casOd']); ?><?php echo $item['casDo'] ? '&ndash;' . esc_html($item['casDo']) : ''; ?></time>
        <div>
          <h3><?php echo esc_html($item['nazov']); ?></h3>
          <?php if ($item['popis']): ?><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($item['popis']), 26, '…')); ?></p><?php endif; ?>
        </div>
        <span><?php echo esc_html(sprintf('%02d', $ri + 1)); ?></span>
      </article>
      <?php endforeach; ?>
    </div>
    <?php $i++; endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty" style="color:var(--paper);opacity:.7">Program zatiaľ nie je zverejnený.</div>
  <?php endif; ?>
</div>
</section>

<section class="section installations stack-panel stack-panel-scroll" id="instalacie" aria-labelledby="installations-title">
<div class="stack-inner">
  <div class="section-label reveal"><span>03</span> Inštalácie</div>
  <div class="installations-head reveal">
    <h2 id="installations-title">Približne <?php echo (int) $dielaCount; ?> diel rozsvieti sídlisko Píly.</h2>
    <p><strong><?php echo (int) $dielaCount; ?> bodov</strong><br>Každé dielo má vlastnú kartu, autora, anotáciu a presné miesto na mape.</p>
  </div>
  <div class="installation-grid">
    <?php if (!$diela): ?>
    <p class="empty" style="color:var(--muted)">Diela zatiaľ nie sú pridané — pridaj ich v administrácii (NOX:ART &rsaquo; Diela).</p>
    <?php endif; ?>
    <?php foreach ($diela as $i => $d): $u = $umelecById[$d['umelecId']] ?? null; $visual = $visualClasses[$i % count($visualClasses)]; $typ = $d['typ'] ?: 'Inštalácia'; ?>
    <article class="installation-card reveal" id="work-<?php echo esc_attr($d['id']); ?>" data-title="<?php echo esc_attr($d['nazov']); ?>" data-type="<?php echo esc_attr($typ); ?>" data-work="<?php echo esc_attr($d['id']); ?>" data-miesto="<?php echo esc_attr($d['miestoId'] ?: ''); ?>">
      <?php if ($d['foto']): ?>
      <div class="card-visual" aria-hidden="true" style="background-image:url('<?php echo esc_url($d['foto']); ?>');background-size:cover;background-position:center"></div>
      <?php else: ?>
      <div class="card-visual <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <div class="card-top"><span><?php echo esc_html(sprintf('%02d', $i + 1)); ?> / <?php echo (int) $dielaCount; ?></span><span><?php echo esc_html($typ); ?></span></div>
      <h3><?php echo esc_html($d['nazov']); ?></h3>
      <p><?php echo esc_html($d['popis'] ? wp_trim_words(wp_strip_all_tags($d['popis']), 22, '…') : ($u ? 'Dielo od ' . $u['meno'] . '.' : 'Popis čoskoro doplníme.')); ?></p>
      <?php if ($d['miestoId']): ?>
      <a href="#mapa" data-show-on-map="<?php echo esc_attr($d['id']); ?>" aria-label="Ukázať dielo <?php echo esc_attr($d['nazov']); ?> na mape">Ukázať na mape <span aria-hidden="true">↗</span></a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</div>
</section>

<section class="section info stack-panel" id="info" aria-labelledby="info-title">
<div class="stack-inner">
  <div class="section-label reveal"><span>04</span> Mapa a praktické info</div>
  <div class="info-grid">
    <div class="route-map reveal" id="mapa" aria-label="Mapa festivalových diel">
      <div class="route-map-head">
        <p>Mapa diel</p>
        <span><?php echo (int) count($miesta); ?> miest / Sídlisko Píly</span>
      </div>
      <div class="route-map-stage">
        <div class="site-map-wrap"><div id="nox-site-map" class="site-map"></div></div>
      </div>
      <p class="route-note">Klikni na značku na mape, alebo použi „Ukázať na mape&ldquo; pri diele vyššie.</p>
    </div>
    <div class="info-content reveal">
      <h2 id="info-title"><?php echo (int) $dielaCount; ?> diel. Jedna nočná trasa.</h2>
      <dl class="info-list">
        <div><dt>Kedy</dt><dd>30.&ndash;31. október 2026</dd></div>
        <div><dt>Kde</dt><dd>Sídlisko Píly, Prievidza</dd></div>
        <div><dt>Diela</dt><dd>Približne <?php echo (int) $dielaCount; ?> svetelných, digitálnych a zvukových inštalácií</dd></div>
        <div><dt>Odporúčanie</dt><dd>Teplé oblečenie, pohodlná obuv a nabitý telefón</dd></div>
      </dl>
      <a class="button button-dark" href="https://maps.google.com/?q=S%C3%ADdlisko+P%C3%ADly+Prievidza" target="_blank" rel="noreferrer">Otvoriť polohu <span aria-hidden="true">↗</span></a>
    </div>
  </div>
</div>
</section>

<section class="section partners stack-panel" id="partneri" aria-labelledby="partners-title">
<div class="stack-inner">
  <div class="section-label reveal"><span>05</span> Partneri</div>
  <div class="partners-head reveal">
    <h2 id="partners-title">Festival vzniká vďaka ľuďom a organizáciám, ktoré veria verejnému priestoru.</h2>
    <p>Ďakujeme všetkým, ktorí pomáhajú dostať súčasné umenie do verejného priestoru.</p>
  </div>
  <div class="partner-board reveal" aria-label="Ukážkové pozície partnerov">
    <div class="partner partner-main"><small>Hlavný partner</small><strong>PARTNER</strong></div>
    <div class="partner"><small>Partner</small><strong>LOGO 01</strong></div>
    <div class="partner"><small>Partner</small><strong>LOGO 02</strong></div>
    <div class="partner"><small>Partner</small><strong>LOGO 03</strong></div>
    <div class="partner"><small>Mediálny partner</small><strong>MÉDIÁ</strong></div>
    <div class="partner"><small>Podpora</small><strong>FOND</strong></div>
  </div>
</div>
</section>

<section class="newsletter stack-panel" id="kontakt" aria-labelledby="newsletter-title">
<div class="stack-inner">
  <div class="newsletter-art" aria-hidden="true"><span></span><i></i><b></b></div>
  <div class="newsletter-copy reveal">
    <p class="section-label"><span>06</span> Zostaň v obraze</p>
    <h2 id="newsletter-title">Program, autori a nové diela priamo do e-mailu.</h2>
    <form class="newsletter-form" data-newsletter-form>
      <label class="sr-only" for="email">E-mailová adresa</label>
      <input type="email" id="email" name="email" placeholder="tvoj@email.sk" autocomplete="email" required>
      <button type="submit">Prihlásiť sa <span aria-hidden="true">↗</span></button>
    </form>
    <p class="form-status" data-form-status role="status" aria-live="polite"></p>
    <p class="newsletter-note">Prihlásením súhlasíte so zasielaním noviniek o festivale. Z odberu sa môžete kedykoľvek odhlásiť.</p>
  </div>
</div>
</section>
</div>
</main>

<footer class="site-footer">
  <div class="footer-top">
    <a class="brand brand-footer" href="#top">
      <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="" aria-hidden="true">
      <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" alt="Ars Preuge">
    </a>
    <div class="footer-links">
      <a href="#festival">O festivale</a>
      <a href="#program">Program</a>
      <a href="#instalacie">Diela</a>
      <a href="#info">Mapa + info</a>
    </div>
    <div class="footer-social">
      <a href="#" aria-label="Instagram">Instagram ↗</a>
      <a href="#" aria-label="Facebook">Facebook ↗</a>
    </div>
  </div>
  <div class="footer-wordmark" aria-hidden="true">NOX<span>:</span>ART&rsquo;26</div>
  <div class="footer-bottom">
    <span>© <?php echo esc_html(date('Y')); ?> Ars Preuge</span>
    <span>Prievidza / Slovensko</span>
    <a href="#top">Hore ↑</a>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
