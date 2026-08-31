<?php
if (!defined('ABSPATH')) exit;

function nox_art_enqueue_assets() {
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'nox_art')) return;

    wp_enqueue_style('nox-art-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&family=Newsreader:opsz,wght@6..72,300;6..72,400;6..72,500&display=swap', [], null);
    wp_enqueue_style('nox-art-mapbox-css', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css', [], '3.1.2');
    wp_enqueue_script('nox-art-mapbox-js', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js', [], '3.1.2', true);

    wp_register_style('nox-art-style', false, [], NOX_ART_VERSION);
    wp_enqueue_style('nox-art-style');
    wp_add_inline_style('nox-art-style', nox_art_asset('app.css'));

    wp_register_script('nox-art-app-js', false, ['nox-art-mapbox-js'], NOX_ART_VERSION, true);
    wp_localize_script('nox-art-app-js', 'NOX_ART_DATA', nox_art_build_data());
    wp_localize_script('nox-art-app-js', 'NOX_ART_MAP', [
        'token' => nox_art_mapbox_token(),
        'style' => nox_art_mapbox_style(),
    ]);
    wp_add_inline_script('nox-art-app-js', nox_art_asset('app.js'));
    wp_enqueue_script('nox-art-app-js');
}
add_action('wp_enqueue_scripts', 'nox_art_enqueue_assets');

function nox_art_shortcode() {
    ob_start();
    ?>
    <div class="nox-art-app">
      <header>
        <h1>NOX:ART</h1>
        <span class="sub">festival · miesta a diela</span>
        <span class="spacer"></span>
        <button class="tool" data-tab="mapa" aria-pressed="false">Mapa</button>
        <button class="tool" data-tab="diela" aria-pressed="false">Diela</button>
        <button class="tool" data-tab="umelci" aria-pressed="false">Umelci</button>
        <button class="tool" data-tab="program" aria-pressed="false">Program</button>
      </header>
      <main>
        <div id="na-viewport">
          <div id="na-map"></div>
          <?php if (nox_art_mapbox_style()): ?><div class="geobadge">WGS84 · <?php echo esc_html(nox_art_mapbox_style()); ?></div><?php endif; ?>
        </div>
        <aside id="na-panel"></aside>
        <aside id="na-detail"></aside>
      </main>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('nox_art', 'nox_art_shortcode');
