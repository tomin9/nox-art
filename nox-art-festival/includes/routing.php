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
    $pages = get_pages(['meta_key' => '_wp_page_template', 'number' => 0]);
    foreach ($pages as $page) {
        if (!in_array(get_page_template_slug($page->ID), $sablony, true)) continue;
        $uri = get_page_uri($page->ID);
        if ($uri) $cesty[] = $uri;
    }
    return $cesty;
}

function nox_art_route_query_vars($vars) {
    $vars[] = 'nox_view';
    $vars[] = 'nox_item';
    return $vars;
}
add_filter('query_vars', 'nox_art_route_query_vars');

function nox_art_route_rules() {
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
    $odtlacok = md5(implode('|', nox_art_route_page_paths()) . '|' . NOX_ART_VERSION);
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
