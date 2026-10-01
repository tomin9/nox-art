<?php
if (!defined('ABSPATH')) exit;

/**
 * Šablóny festivalových stránok (adaptovaný webový prototyp NOX:ART v5).
 *
 * Obsah je rozdelený na samostatné sekcie (templates/parts/*.php) a každá
 * šablóna určuje, ktoré z nich sa na danej stránke vykreslia. Vďaka tomu sa
 * dá festival poskládať z klasických WP podstránok (Program, Diela,
 * Partneri, Kontakt) namiesto jednej dlhej stránky – stránka
 * potom scrolluje úplne normálne, bez animácií a bez sekania.
 *
 * Menu aj odkazy v pätičke sa skládajú automaticky: ak podstránka pre danú
 * sekciu existuje, odkazuje sa na ňu; ak nie, použije sa kotva (#program…)
 * na stránke, ktorá má všetky sekcie pohromade.
 */

/**
 * Mapa: názov šablóny → sekcie, ktoré vykreslí (v tomto poradí).
 */
function nox_art_site_template_map() {
    return [
        'nox-art-site-template.php'    => ['label' => 'NOX:ART — Celá stránka (všetky sekcie)', 'sections' => ['hero', 'program', 'about', 'partners', 'newsletter']],
        'nox-art-page-domov.php'       => ['label' => 'NOX:ART — Domov (úvod)',                'sections' => ['hero', 'about', 'newsletter']],
        'nox-art-page-festival.php'    => ['label' => 'NOX:ART — O festivale',                 'sections' => ['about', 'newsletter']],
        // Program a diela sú jedna sekcia – program festivalu obsahuje aj
        // diela aj sprievodný program, delia sa len kategóriami. Šablóna
        // "Diela" zostáva zaregistrovaná ako alias, aby sa nerozbila
        // stránka, ktorá ju už používa.
        'nox-art-page-program.php'     => ['label' => 'NOX:ART — Program a diela',             'sections' => ['program', 'newsletter']],
        'nox-art-page-diela.php'       => ['label' => 'NOX:ART — Program a diela (alias)',     'sections' => ['program', 'newsletter']],
        // Mapa je dnes súčasťou sekcie Program, samostatná sekcia s mapou
        // a praktickým infom zanikla – šablóna zostáva ako alias, aby sa
        // nerozbila stránka, ktorá ju už používa.
        'nox-art-page-mapa.php'        => ['label' => 'NOX:ART — Mapa a info (alias)',         'sections' => ['program', 'newsletter']],
        'nox-art-page-partneri.php'    => ['label' => 'NOX:ART — Partneri',                    'sections' => ['partners', 'newsletter']],
        'nox-art-page-kontakt.php'     => ['label' => 'NOX:ART — Kontakt / newsletter',        'sections' => ['newsletter']],
    ];
}

function nox_art_register_site_template($templates) {
    foreach (nox_art_site_template_map() as $file => $def) {
        $templates[$file] = $def['label'];
    }
    return $templates;
}
add_filter('theme_page_templates', 'nox_art_register_site_template');

/**
 * Ktorá z našich šablón je práve použitá (alebo '' ak žiadna).
 */
function nox_art_site_current_template() {
    if (!is_page()) return '';
    $slug = get_page_template_slug(get_queried_object_id());
    return isset(nox_art_site_template_map()[$slug]) ? $slug : '';
}

/**
 * Sekcie, ktoré sa majú na aktuálnej stránke vykresliť.
 */
function nox_art_site_sections() {
    $slug = nox_art_site_current_template();
    if (!$slug) return [];
    return nox_art_site_template_map()[$slug]['sections'];
}

/**
 * Všetky naše šablóny vykresľuje ten istý súbor – líšia sa len zoznamom sekcií.
 */
function nox_art_load_site_template($template) {
    if (nox_art_site_current_template()) {
        $custom = NOX_ART_DIR . 'templates/nox-art-site-template.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
add_filter('template_include', 'nox_art_load_site_template');

/**
 * Kotva (id sekcie) pre danú sekciu – zhoduje sa s id v templates/parts/*.php.
 */
function nox_art_site_anchors() {
    return [
        'hero' => 'top',
        'about' => 'festival',
        'program' => 'program',
        'partners' => 'partneri',
        'newsletter' => 'kontakt',
    ];
}

/**
 * Ktorá sekcia má "svoju" podstránku – šablóna, ktorá ju vykresľuje ako hlavnú.
 */
function nox_art_site_section_templates() {
    return [
        'hero' => ['nox-art-page-domov.php', 'nox-art-site-template.php'],
        'about' => ['nox-art-page-festival.php'],
        'program' => ['nox-art-page-program.php', 'nox-art-page-diela.php', 'nox-art-page-mapa.php'],
        'partners' => ['nox-art-page-partneri.php'],
        'newsletter' => ['nox-art-page-kontakt.php'],
    ];
}

/**
 * URL stránky, ktorá používa danú šablónu (alebo '' ak taká stránka nie je).
 * Zisťuje sa jedným dopytom a drží sa v pamäti počas celého requestu.
 */
function nox_art_site_template_url($slug) {
    static $urls = null;
    if ($urls === null) {
        $urls = [];
        $pages = get_pages([
            'meta_key' => '_wp_page_template',
            'number' => 0,
            'sort_column' => 'menu_order,post_title',
        ]);
        foreach ($pages as $page) {
            $tpl = get_page_template_slug($page->ID);
            if ($tpl && !isset($urls[$tpl])) $urls[$tpl] = get_permalink($page->ID);
        }
    }
    return $urls[$slug] ?? '';
}

/**
 * Odkaz na sekciu: ak je sekcia na aktuálnej stránke, stačí kotva; inak
 * odkážeme na jej podstránku a ak ani tá neexistuje, na stránku so všetkými
 * sekciami. Vracia hotový, escapovaný atribút href.
 */
function nox_art_site_link($section, $anchor = null) {
    $anchors = nox_art_site_anchors();
    $anchor = $anchor ?: ($anchors[$section] ?? '');
    $hash = $anchor ? '#' . $anchor : '';

    if (in_array($section, nox_art_site_sections(), true)) return esc_url($hash);

    foreach (nox_art_site_section_templates()[$section] ?? [] as $slug) {
        $url = nox_art_site_template_url($slug);
        if ($url) return esc_url($url . $hash);
    }

    $all = nox_art_site_template_url('nox-art-site-template.php');
    return esc_url($all ? $all . $hash : $hash);
}

/**
 * Položky hlavného menu (a odkazov v pätičke).
 */
function nox_art_site_nav_items() {
    $current = nox_art_site_sections();
    $items = [];
    foreach ([
        'program' => 'Program a diela',
        'about' => 'O festivale',
        'partners' => 'Partneri',
    ] as $section => $label) {
        $items[] = [
            'label' => $label,
            'url' => nox_art_site_link($section),
            // Za "aktuálnu" považujeme položku len na podstránke, ktorá danú
            // sekciu skutočne obsahuje – nie na stránke so všetkým naraz.
            'current' => in_array($section, $current, true) && count($current) <= 2,
        ];
    }
    return $items;
}

function nox_art_site_enqueue_assets() {
    $sections = nox_art_site_sections();
    if (!$sections) return;

    $site_css_path = NOX_ART_DIR . 'assets/site.css';
    $site_js_path = NOX_ART_DIR . 'assets/site.js';

    // Verzia podľa času poslednej úpravy súboru – nie pevné číslo z hlavičky
    // pluginu – aby prehliadač/cache/optimalizačné pluginy nikdy neservírovali
    // zastaranú kešovanú verziu po tom, čo GitHub Plugin Sync nahradí súbory.
    wp_enqueue_style('nox-art-site-css', NOX_ART_URL . 'assets/site.css', [], file_exists($site_css_path) ? filemtime($site_css_path) : NOX_ART_VERSION);

    // Mapbox naťahujeme len na stránkach, kde je mapa – inak sú to zbytočné
    // ~800 kB skriptu a štýlov na každej podstránke.
    $deps = [];
    // Mapa je súčasťou sekcie Program (vpravo vedľa zoznamu diel).
    $has_map = in_array('program', $sections, true);
    if ($has_map) {
        wp_enqueue_style('nox-art-mapbox-css', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css', [], '3.1.2');
        wp_enqueue_script('nox-art-mapbox-js', 'https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js', [], '3.1.2', true);
        $deps[] = 'nox-art-mapbox-js';
    }

    wp_enqueue_script('nox-art-site-js', NOX_ART_URL . 'assets/site.js', $deps, file_exists($site_js_path) ? filemtime($site_js_path) : NOX_ART_VERSION, true);

    if ($has_map) {
        $map = nox_art_get_map_settings();
        wp_localize_script('nox-art-site-js', 'NOX_SITE_MAP', [
            'token' => $map['token'],
            'style' => $map['style'],
            'miesta' => nox_art_data_miesta(),
            'diela' => nox_art_data_diela(),
        ]);
    }
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
