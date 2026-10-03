<?php
if (!defined('ABSPATH')) exit;

/**
 * Adresy jednotlivých pohľadov sekcie Program: okrem samotnej stránky
 * fungujú aj adresy ako /nox.art/diela, /nox.art/sprievodny-program alebo
 * /nox.art/diela/the-moon. WordPress by na takej adrese inak vrátil 404,
 * preto si pre každú stránku s našou šablónou pridáme prepisovacie pravidlo,
 * ktoré ju načíta a cestu odovzdá ako parametre. Zvyšok (prepnutie filtra,
 * otvorenie detailu) spraví site.js, lebo obsah je na stránke už celý.
 */

/**
 * Cesty stránok, ktoré používajú niektorú z našich šablón (napr. "nox.art").
 */
function nox_art_route_page_paths() {
    static $cesty = null;
    if ($cesty !== null) return $cesty;

    $cesty = [];
    $sablony = array_keys(nox_art_site_template_map());
    $pages = get_pages(['number' => 0]);
    foreach ($pages as $page) {
        if (!in_array(nox_art_site_template_for_page($page->ID), $sablony, true)) continue;
        $uri = get_page_uri($page->ID);
        if ($uri) $cesty[] = $uri;
    }

    // Stránka priradená v nastaveniach nemusí mať šablónu z editora.
    $priradene = nox_art_site_assigned_page();
    if ($priradene['page']) {
        $uri = get_page_uri($priradene['page']);
        if ($uri) $cesty[] = $uri;
    }

    return array_unique($cesty);
}

function nox_art_route_query_vars($vars) {
    $vars[] = 'nox_view';
    $vars[] = 'nox_item';
    return $vars;
}
add_filter('query_vars', 'nox_art_route_query_vars');

/**
 * Slugy, ktoré môžu stáť v adrese ako pohľad – kategórie z filtra plus
 * harmonogram. Potrebujeme ich, keď je festival úvodnou stránkou webu:
 * vtedy nesmieme zobrať každú adresu na prvej úrovni, len tieto.
 */
function nox_art_route_view_slugs() {
    $slugy = ['harmonogram'];
    $terms = get_terms(['taxonomy' => 'nox_kategoria', 'hide_empty' => false]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) $slugy[] = $term->slug;
    }
    return array_unique(array_filter($slugy));
}

function nox_art_route_rules() {
    // Festival ako úvodná stránka samostatného webu (noxart.sk/diela/).
    $front = (int) get_option('page_on_front');
    if ($front && in_array('program', nox_art_site_sections_for_page($front), true)) {
        $slugy = implode('|', array_map(function ($slug) { return preg_quote($slug, '#'); }, nox_art_route_view_slugs()));
        if ($slugy) {
            add_rewrite_rule(
                '^(' . $slugy . ')(?:/([^/]+))?/?$',
                'index.php?page_id=' . $front . '&nox_view=$matches[1]&nox_item=$matches[2]',
                'top'
            );
        }
    }

    foreach (nox_art_route_page_paths() as $cesta) {
        $regex = '^' . preg_quote($cesta, '#') . '/([^/]+)(?:/([^/]+))?/?$';
        add_rewrite_rule(
            $regex,
            'index.php?pagename=' . $cesta . '&nox_view=$matches[1]&nox_item=$matches[2]',
            'top'
        );
    }
}
add_action('init', 'nox_art_route_rules', 20);

/**
 * Pravidlá treba po pridaní (alebo po premenovaní stránky) raz prepísať.
 * Namiesto flushovania pri každom načítaní si pamätáme, z čoho boli
 * vyrobené – a prepíšeme ich, len keď sa to zmení.
 */
function nox_art_route_maybe_flush() {
    $odtlacok = md5(
        implode('|', nox_art_route_page_paths()) . '|' .
        implode('|', nox_art_route_view_slugs()) . '|' .
        get_option('page_on_front') . '|' . NOX_ART_VERSION
    );
    if (get_option('nox_art_routes_hash') === $odtlacok) return;
    flush_rewrite_rules(false);
    update_option('nox_art_routes_hash', $odtlacok);
}
add_action('wp_loaded', 'nox_art_route_maybe_flush', 20);

/**
 * Čo si stránka vypýtala v adrese – pohľad (slug kategórie alebo
 * "harmonogram") a prípadne konkrétna položka.
 */
function nox_art_route_current() {
    return [
        'view' => sanitize_title(get_query_var('nox_view')),
        'item' => sanitize_title(get_query_var('nox_item')),
    ];
}

/**
 * Základ adresy, na ktorý si skript dopisuje pohľad a položku.
 */
function nox_art_route_base() {
    $id = get_queried_object_id();
    $url = $id ? get_permalink($id) : home_url('/');
    // Na vlastnej festivalovej doméne je základom jej koreň (includes/domain.php).
    return apply_filters('nox_art_route_base', user_trailingslashit(trailingslashit($url)));
}
