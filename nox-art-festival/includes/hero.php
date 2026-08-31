<?php
if (!defined('ABSPATH')) exit;

/**
 * Úvodná hero sekcia festivalu (shortcode [nox_art_hero]) – samostatná od
 * appky [nox_art], vkladá sa nad ňu na tú istú stránku. Texty sú zatiaľ
 * napevno podľa plagátu; odkazy v menu a tlačidlách sú placeholder "#",
 * kým nebudú známe cieľové URL (samostatné stránky O festivale/Info/…).
 */
function nox_art_hero_enqueue_assets() {
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'nox_art_hero')) return;

    wp_enqueue_style('nox-art-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap', [], null);

    wp_register_style('nox-art-hero-style', false, [], NOX_ART_VERSION);
    wp_enqueue_style('nox-art-hero-style');
    wp_add_inline_style('nox-art-hero-style', nox_art_asset('hero.css'));
}
add_action('wp_enqueue_scripts', 'nox_art_hero_enqueue_assets');

function nox_art_hero_shortcode() {
    ob_start();
    ?>
    <div class="nox-art-hero">
      <nav class="nh-nav">
        <div class="nh-logo"><span class="nh-logo-main">NOX:ART</span><span class="nh-logo-sub">arspreuge</span></div>
        <ul class="nh-menu">
          <li><a href="#">O festivale</a></li>
          <li><a href="#">Program</a></li>
          <li><a href="#">Inštalácie</a></li>
          <li><a href="#">Info</a></li>
          <li><a href="#">Partneri</a></li>
        </ul>
        <a href="#" class="nh-follow">Sleduj nás <span class="nh-arrow">↗</span></a>
      </nav>

      <div class="nh-stage">
        <svg class="nh-shapes" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
          <defs>
            <linearGradient id="nhGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#f5941d"/>
              <stop offset="100%" stop-color="#e8407c"/>
            </linearGradient>
            <linearGradient id="nhGrad2" x1="100%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#e8407c"/>
              <stop offset="100%" stop-color="#f5941d"/>
            </linearGradient>
          </defs>
          <path d="M70,110 C210,30 260,170 195,270 C130,370 55,410 95,540 C125,635 250,655 335,600" fill="none" stroke="url(#nhGrad1)" stroke-width="72" stroke-linecap="round"/>
          <path d="M190,50 C310,10 350,130 305,235 C260,340 195,380 235,485 C265,565 380,585 445,520" fill="none" stroke="url(#nhGrad2)" stroke-width="60" stroke-linecap="round"/>
        </svg>

        <div class="nh-badge" aria-hidden="true"><span>A</span><span>P</span></div>

        <div class="nh-content">
          <h1 class="nh-title">NOX:ART</h1>
          <p class="nh-subtitle">Medzinárodný festival<br>súčasného umenia</p>

          <p class="nh-eyebrow">Sídlisko Píly</p>
          <p class="nh-dates">30&ndash;31<br>Október<br>&rsquo;26</p>

          <p class="nh-desc">Keď sa zotmie,<br>Prievidza sa rozsvieti umením.<br>NOX:ART premieňa verejný priestor na galériu svetla, obrazu a zážitkov.</p>

          <div class="nh-cta">
            <a href="#" class="nh-btn nh-btn-solid">Program <span class="nh-arrow">↗</span></a>
            <a href="#" class="nh-btn nh-btn-outline">O festivale</a>
          </div>
        </div>

        <div class="nh-side-label" aria-hidden="true">Umenie vo verejnom priestore</div>
        <div class="nh-tag" aria-hidden="true">#NOXART26<span class="nh-tag-arrow">↓</span></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('nox_art_hero', 'nox_art_hero_shortcode');
