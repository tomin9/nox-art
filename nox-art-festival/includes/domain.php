<?php
if (!defined('ABSPATH')) exit;

/**
 * Festival na vlastnej doméne (napr. noxart.sk) bez druhej inštalácie
 * WordPressu. Doména sa na hostingu nasmeruje na ten istý web; tento súbor
 * sa postará o zvyšok:
 *
 *  - na festivalovej doméne je festivalová stránka rovno na úvodnej adrese
 *    a jej pohľady majú krátke adresy (noxart.sk/diela/, /diela/the-moon/),
 *  - odkazy a adresy súborov sa prepisujú na festivalovú doménu, aby sa
 *    stránka neodkazovala späť na pôvodnú,
 *  - WordPress na tejto doméne nepresmerováva späť na hlavnú adresu webu,
 *  - pôvodná adresa (napr. novesidlisko.sk/nox.art/) sa natrvalo presmeruje
 *    na novú doménu, nech obsah nie je na dvoch miestach naraz.
 */

define('NOX_ART_DOMAIN_OPTION', 'nox_art_festival_domain');

/**
 * Nastavená festivalová doména (len hostiteľ, bez protokolu a lomky).
 */
function nox_art_festival_domain() {
    $domena = get_option(NOX_ART_DOMAIN_OPTION, '');
    $domena = is_string($domena) ? strtolower(trim($domena)) : '';
    if ($domena === '') return '';
    $domena = preg_replace('#^https?://#', '', $domena);
    return trim($domena, '/ ');
}

/**
 * Beží aktuálna požiadavka na festivalovej doméne?
 */
function nox_art_on_festival_domain() {
    $domena = nox_art_festival_domain();
    if (!$domena) return false;
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']))) : '';
    $host = preg_replace('#:\d+$#', '', $host);
    return $host === $domena || $host === 'www.' . $domena;
}

/**
 * Stránka, ktorú festivalová doména zobrazuje ako úvodnú – prvá stránka
 * so šablónou, ktorá obsahuje sekciu Program.
 */
function nox_art_festival_page_id() {
    static $id = null;
    if ($id !== null) return $id;

    $id = 0;
    $vhodne = [];
    foreach (nox_art_site_template_map() as $subor => $def) {
        if (in_array('program', $def['sections'], true)) $vhodne[] = $subor;
    }

    foreach (get_pages(['number' => 0]) as $page) {
        if (!in_array(nox_art_site_template_for_page($page->ID), $vhodne, true)) continue;
        $id = $page->ID;
        break;
    }
    return $id;
}

/**
 * Adresy: na festivalovej doméne prepíšeme hostiteľa, nech stránka
 * neodkazuje na pôvodnú doménu (odkazy v menu, CSS, JS, obrázky).
 */
function nox_art_festival_swap_host($url) {
    if (!is_string($url) || !nox_art_on_festival_domain()) return $url;
    $domena = nox_art_festival_domain();
    $povodny = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if (!$domena || !$povodny) return $url;
    return str_replace('//' . $povodny, '//' . $domena, $url);
}

function nox_art_festival_url_filters() {
    if (!nox_art_on_festival_domain()) return;
    foreach (['home_url', 'content_url', 'plugins_url', 'upload_dir_baseurl', 'theme_root_uri'] as $hook) {
        add_filter($hook, 'nox_art_festival_swap_host');
    }
    add_filter('upload_dir', function ($dirs) {
        $dirs['url'] = nox_art_festival_swap_host($dirs['url']);
        $dirs['baseurl'] = nox_art_festival_swap_host($dirs['baseurl']);
        return $dirs;
    });
    // WordPress by inak presmeroval na adresu webu v Nastaveniach.
    remove_filter('template_redirect', 'redirect_canonical');
    add_filter('redirect_canonical', '__return_false');
    // Vlastný kanonický odkaz nižšie – ten od WordPressu by ukazoval na pôvodnú doménu.
    remove_action('wp_head', 'rel_canonical');
}
add_action('init', 'nox_art_festival_url_filters', 5);

/**
 * Smerovanie na festivalovej doméne. Nerobíme to prepisovacími pravidlami
 * (tie platia pre všetky domény a prebili by adresy hlavného webu), ale
 * priamo pri spracovaní požiadavky.
 */
function nox_art_festival_parse_request($wp) {
    if (!nox_art_on_festival_domain()) return;

    $page_id = nox_art_festival_page_id();
    if (!$page_id) return;

    $cesta = trim(wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');

    // Administrácia, prihlásenie, feed a podobné nechávame tak.
    if ($cesta && preg_match('#^(wp-admin|wp-login|wp-json|wp-content|wp-includes|feed|robots\.txt|sitemap)#', $cesta)) return;

    $casti = $cesta === '' ? [] : explode('/', $cesta);

    $wp->query_vars = [
        'page_id' => $page_id,
        'nox_view' => isset($casti[0]) ? sanitize_title($casti[0]) : '',
        'nox_item' => isset($casti[1]) ? sanitize_title($casti[1]) : '',
    ];
    $wp->matched_rule = '';
    $wp->matched_query = '';
}
add_action('parse_request', 'nox_art_festival_parse_request');

/**
 * Základ adresy pre skript: na festivalovej doméne je to jej koreň.
 */
function nox_art_festival_route_base($base) {
    if (!nox_art_on_festival_domain()) return $base;
    return trailingslashit('https://' . nox_art_festival_domain());
}
add_filter('nox_art_route_base', 'nox_art_festival_route_base');

/**
 * Pôvodná adresa festivalovej stránky sa natrvalo presmeruje na novú
 * doménu – obsah tak nie je na dvoch miestach a Google vie, ktorá adresa
 * je tá správna.
 */
function nox_art_festival_redirect_old_url() {
    if (is_admin() || nox_art_on_festival_domain()) return;

    $domena = nox_art_festival_domain();
    if (!$domena || !get_option('nox_art_festival_domain_redirect', '1')) return;

    $page_id = nox_art_festival_page_id();
    if (!$page_id || !is_page($page_id)) return;

    $view = sanitize_title(get_query_var('nox_view'));
    $item = sanitize_title(get_query_var('nox_item'));
    $cesta = trim($view . '/' . $item, '/');

    wp_redirect('https://' . $domena . '/' . ($cesta ? $cesta . '/' : ''), 301);
    exit;
}
add_action('template_redirect', 'nox_art_festival_redirect_old_url', 1);

/**
 * Kanonický odkaz na festivalovú doménu – poistka pre vyhľadávače.
 */
function nox_art_festival_canonical() {
    if (!nox_art_on_festival_domain()) return;
    $cesta = trim(wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
    printf(
        '<link rel="canonical" href="%s">' . "\n",
        esc_url('https://' . nox_art_festival_domain() . '/' . ($cesta ? $cesta . '/' : ''))
    );
}
add_action('wp_head', 'nox_art_festival_canonical', 1);
