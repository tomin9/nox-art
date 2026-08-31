<?php
if (!defined('ABSPATH')) exit;

/**
 * Celostránková šablóna festivalu (adaptovaný webový prototyp NOX:ART v5) –
 * vyberie sa v editore WP stránky ako "Šablóna stránky". Vykresľuje kompletnú
 * stránku (hlavička/menu/hero/O festivale/Program/Diela/Mapa/Partneri/
 * Newsletter/pätička) bez divadelnej hlavičky/päty aktívnej témy; obsah
 * sekcií Diela a Program je naťahaný z CPT (nox_dielo, nox_program).
 */
function nox_art_register_site_template($templates) {
    $templates['nox-art-site-template.php'] = 'NOX:ART — Festival (celá stránka)';
    return $templates;
}
add_filter('theme_page_templates', 'nox_art_register_site_template');

function nox_art_load_site_template($template) {
    if (is_page_template('nox-art-site-template.php')) {
        $custom = NOX_ART_DIR . 'templates/nox-art-site-template.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
add_filter('template_include', 'nox_art_load_site_template');

function nox_art_site_enqueue_assets() {
    if (!is_page_template('nox-art-site-template.php')) return;

    $map = nox_art_get_map_settings();

    wp_enqueue_style('nox-art-mapbox-css', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css', [], '3.1.2');
    wp_enqueue_script('nox-art-mapbox-js', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js', [], '3.1.2', true);

    wp_enqueue_style('nox-art-site-css', NOX_ART_URL . 'assets/site.css', [], NOX_ART_VERSION);

    wp_enqueue_script('nox-art-site-js', NOX_ART_URL . 'assets/site.js', ['nox-art-mapbox-js'], NOX_ART_VERSION, true);
    wp_localize_script('nox-art-site-js', 'NOX_SITE_MAP', [
        'token' => $map['token'],
        'style' => $map['style'],
        'miesta' => nox_art_data_miesta(),
        'diela' => nox_art_data_diela(),
    ]);
}
add_action('wp_enqueue_scripts', 'nox_art_site_enqueue_assets');

/**
 * Pomocné funkcie použité v templates/nox-art-site-template.php.
 */
function nox_art_site_asset($file) {
    return esc_url(NOX_ART_URL . 'assets/site/' . $file);
}

function nox_art_site_program_by_day() {
    $days = [];
    foreach (nox_art_data_program() as $item) {
        $key = $item['datum'] ?: 'bez-datumu';
        if (!isset($days[$key])) $days[$key] = [];
        $days[$key][] = $item;
    }
    return $days;
}

function nox_art_site_day_label($datum) {
    $names = [1 => 'Pondelok', 2 => 'Utorok', 3 => 'Streda', 4 => 'Štvrtok', 5 => 'Piatok', 6 => 'Sobota', 7 => 'Nedeľa'];
    $ts = strtotime($datum);
    if (!$ts) return ['Deň', $datum];
    $n = (int) date('N', $ts);
    return [$names[$n] ?? 'Deň', date('j.n.', $ts) . '.'];
}
