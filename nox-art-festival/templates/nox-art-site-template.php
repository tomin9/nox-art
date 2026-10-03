<?php
/**
 * Spoločná šablóna NOX:ART. Vykresľuje kompletnú stránku bez hlavičky/päty
 * aktívnej témy; ktoré sekcie sa na stránke zobrazia, určuje mapa šablón
 * v includes/site-template.php (nox_art_site_sections()).
 *
 * Stránka sa scrolluje úplne normálne – žiadny "stack" efekt, žiadne
 * fixované karty. Obsah je rozdelený na podstránky (Program, Diela,
 * Mapa + info, Partneri, Kontakt), takže na jednej stránke je len toľko
 * obsahu, koľko na ňu patrí.
 */
if (!defined('ABSPATH')) exit;

$sections = nox_art_site_sections();

$diela = nox_art_data_diela();
$miesta = nox_art_data_miesta();
$umelci = nox_art_data_umelci();
$visualClasses = ['visual-one', 'visual-two', 'visual-three', 'visual-four', 'visual-five', 'visual-six', 'visual-seven', 'visual-eight'];
$dielaCount = count($diela);
$umelecById = [];
foreach ($umelci as $u) $umelecById[$u['id']] = $u;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<?php /* viewport-fit=cover: stránka siaha až pod stavový riadok telefónu, takže
   fixná hlavička ho prekryje vlastným pozadím (výplň dopĺňa env(safe-area-inset-top)). */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
  <a class="brand" href="<?php echo nox_art_site_link('hero'); ?>" aria-label="NOX:ART a Ars Preuge — späť na začiatok">
    <picture>
      <source srcset="<?php echo nox_art_site_asset('noxart-official-wordmark.webp'); ?>" type="image/webp">
      <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="NOX:ART" fetchpriority="high" decoding="async">
    </picture>
    <picture>
      <source srcset="<?php echo nox_art_site_asset('ars-preuge-logo.webp'); ?>" type="image/webp">
      <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" width="1200" height="812" alt="Ars Preuge" decoding="async">
    </picture>
  </a>
  <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Otvoriť menu">
    <span></span><span></span>
  </button>
  <nav class="main-nav" id="main-nav" aria-label="Hlavná navigácia">
    <?php foreach (nox_art_site_nav_items() as $item): ?>
    <a href="<?php echo $item['url']; ?>"<?php echo $item['current'] ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
    <?php endforeach; ?>
    <a class="nav-pill" href="<?php echo nox_art_site_link('newsletter'); ?>">Newsletter <span aria-hidden="true">↗</span></a>
    <?php /* Ikony sietí sa spravujú v NOX:ART → Nastavenia; prázdny odkaz
             znamená, že sa ikona nezobrazí. */ ?>
    <?php $socialne = nox_art_get_social_links(); ?>
    <?php if (array_filter($socialne)): ?>
    <span class="nav-social">
      <?php foreach ($socialne as $siet => $url): if (!$url) continue; ?>
      <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(nox_art_social_networks()[$siet]); ?>"><?php echo nox_art_social_icon($siet); ?></a>
      <?php endforeach; ?>
    </span>
    <?php endif; ?>
  </nav>
</header>

<main>
<?php foreach ($sections as $section): ?>
<?php include NOX_ART_DIR . 'templates/parts/' . $section . '.php'; ?>
<?php endforeach; ?>
</main>

<footer class="site-footer">
  <div class="footer-top">
    <a class="brand brand-footer" href="<?php echo nox_art_site_link('hero'); ?>">
      <picture>
        <source srcset="<?php echo nox_art_site_asset('noxart-official-wordmark.webp'); ?>" type="image/webp">
        <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="" aria-hidden="true" loading="lazy" decoding="async">
      </picture>
      <picture>
        <source srcset="<?php echo nox_art_site_asset('ars-preuge-logo.webp'); ?>" type="image/webp">
        <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" width="1200" height="812" alt="Ars Preuge" loading="lazy" decoding="async">
      </picture>
    </a>
    <div class="footer-links">
      <?php foreach (nox_art_site_nav_items() as $item): ?>
      <a href="<?php echo $item['url']; ?>"><?php echo esc_html($item['label']); ?></a>
      <?php endforeach; ?>
    </div>
    <?php if (array_filter($socialne)): ?>
    <div class="footer-social">
      <?php foreach ($socialne as $siet => $url): if (!$url) continue; ?>
      <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php echo nox_art_social_icon($siet); ?><?php echo esc_html(nox_art_social_networks()[$siet]); ?> ↗</a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="footer-wordmark" aria-hidden="true">NOX<span>:</span>ART&rsquo;26</div>
  <div class="footer-bottom">
    <?php /* Kontrola, či web naozaj beží na poslednej verzii súborov:
             stačí k adrese pridať ?nox-debug=1 a dole sa vypíše čas
             poslednej zmeny štýlov a skriptu. */ ?>
    <?php if (isset($_GET['nox-debug'])): ?>
    <span>build <?php echo esc_html(NOX_ART_VERSION); ?> ·
      css <?php echo esc_html(date('j.n. H:i', (int) @filemtime(NOX_ART_DIR . 'assets/site.css'))); ?> ·
      js <?php echo esc_html(date('j.n. H:i', (int) @filemtime(NOX_ART_DIR . 'assets/site.js'))); ?></span>
    <?php endif; ?>
    <span>© <?php echo esc_html(date('Y')); ?> Ars Preuge</span>
    <span>Prievidza / Slovensko</span>
    <a href="#top">Hore ↑</a>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
