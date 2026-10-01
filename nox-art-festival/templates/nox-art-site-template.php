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
  <a class="brand" href="<?php echo nox_art_site_link('hero'); ?>" aria-label="NOX:ART a Ars Preuge — späť na začiatok">
    <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="NOX:ART">
    <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" alt="Ars Preuge">
  </a>
  <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Otvoriť menu">
    <span></span><span></span>
  </button>
  <nav class="main-nav" id="main-nav" aria-label="Hlavná navigácia">
    <?php foreach (nox_art_site_nav_items() as $item): ?>
    <a href="<?php echo $item['url']; ?>"<?php echo $item['current'] ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
    <?php endforeach; ?>
    <a class="nav-pill" href="<?php echo nox_art_site_link('newsletter'); ?>">Sleduj nás <span aria-hidden="true">↗</span></a>
  </nav>
</header>

<main>
<?php
/* Hero funguje ako roletka: scrollovaním sa vysunie hore, zatiaľ čo obsah
   pod ňou stojí. Rieši to čisto CSS (prilepený vnútorný obal), preto tie
   dva obaly. Keď hero na stránke nie je, nič sa nebalí. */
$hasHero = in_array('hero', $sections, true);
$below = array_values(array_diff($sections, ['hero']));
?>
<?php if ($hasHero): ?>
<?php include NOX_ART_DIR . 'templates/parts/hero.php'; ?>
<div class="curtain-below"><div class="curtain-below-inner">
<?php endif; ?>
<?php foreach ($below as $section): ?>
<?php include NOX_ART_DIR . 'templates/parts/' . $section . '.php'; ?>
<?php endforeach; ?>
<?php if ($hasHero): ?>
</div></div>
<?php endif; ?>
</main>

<footer class="site-footer">
  <div class="footer-top">
    <a class="brand brand-footer" href="<?php echo nox_art_site_link('hero'); ?>">
      <img class="brand-nox-logo" src="<?php echo nox_art_site_asset('noxart-official-wordmark.png'); ?>" width="561" height="111" alt="" aria-hidden="true">
      <img class="brand-ars-logo" src="<?php echo nox_art_site_asset('ars-preuge-logo.png'); ?>" alt="Ars Preuge">
    </a>
    <div class="footer-links">
      <?php foreach (nox_art_site_nav_items() as $item): ?>
      <a href="<?php echo $item['url']; ?>"><?php echo esc_html($item['label']); ?></a>
      <?php endforeach; ?>
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
