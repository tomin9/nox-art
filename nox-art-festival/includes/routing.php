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
function nox_art_route_aliases() {
    $aliasy = get_option('nox_art_slug_aliases', []);
    if (!is_array($aliasy)) $aliasy = [];
    // Duplicitná kategória z importu zo starého webu.
    $aliasy += ['partnerske-podniky' => 'podniky'];
    return apply_filters('nox_art_route_aliases', $aliasy);
}

/**
 * Sekcie stránky, ktoré majú vlastnú adresu (noxart.sk/program/). Obsah je
 * ten istý celý web, no každá adresa má vlastný titulok, popis a kanonický
 * odkaz (nox_art_site_seo) a po otvorení sa stránka posunie na svoju sekciu.
 */
function nox_art_route_sections() {
    return [
        'program' => [
            'section' => 'program',
            'anchor' => 'program',
            'title' => 'Program a diela',
            'description' => 'Diela, sprievodný program a časový harmonogram festivalu NOX:ART na sídlisku Píly v Prievidzi, 30.–31. októbra 2026.',
        ],
        'o-festivale' => [
            'section' => 'about',
            'anchor' => 'festival',
            'title' => 'O festivale',
            'description' => 'NOX:ART: dve noci súčasného umenia vo verejnom priestore sídliska Píly v Prievidzi, 30.–31. októbra 2026.',
        ],
        'partneri' => [
            'section' => 'partners',
            'anchor' => 'partneri',
            'title' => 'Partneri',
            'description' => 'Organizátor, generálny partner, partneri a mediálni partneri festivalu NOX:ART v Prievidzi.',
        ],
    ];
}

function nox_art_route_view_slugs() {
    $slugy = array_merge(['harmonogram', 'autori'], array_keys(nox_art_route_sections()), array_keys(nox_art_route_aliases()));
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
    $pohlad = sanitize_title(get_query_var('nox_view'));
    if (isset(nox_art_route_sections()[$pohlad])) {
        return ['view' => '', 'item' => '', 'section' => $pohlad];
    }
    // Alias platí len pre slug, ktorý už neexistuje a vedie na kategóriu,
    // ktorá existuje – živá kategória sa nikdy nepreklápa inam.
    $aliasy = nox_art_route_aliases();
    if (isset($aliasy[$pohlad])
        && !get_term_by('slug', $pohlad, 'nox_kategoria')
        && get_term_by('slug', $aliasy[$pohlad], 'nox_kategoria')) {
        $pohlad = $aliasy[$pohlad];
    }
    return [
        'section' => '',
        'view' => $pohlad,
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

/**
 * Adresa pohľadu alebo sekcie (/program/, /diela/ …) sa načíta ako úvodná
 * stránka (page_id). WordPress by ju kanonickým presmerovaním poslal späť na
 * adresu stránky ("/"), preto pri našich adresách presmerovanie vypneme.
 */
function nox_art_route_keep_url($redirect, $requested) {
    if (get_query_var('nox_view')) return false;
    return $redirect;
}
add_filter('redirect_canonical', 'nox_art_route_keep_url', 10, 2);

/**
 * Poistka k prepisovacím pravidlám: ak sa pravidlá z nejakého dôvodu
 * neprepísali (keš, hosting, zmena nastavení), adresu pohľadu či sekcie
 * rozpoznáme sami podľa cesty a načítame úvodnú festivalovú stránku.
 */
function nox_art_route_parse_request($wp) {
    if (!empty($wp->query_vars['nox_view']) || is_admin()) return;

    $front = (int) get_option('page_on_front');
    if (!$front || !in_array('program', nox_art_site_sections_for_page($front), true)) return;

    $cesta = wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $domov = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    $cesta = trim($cesta, '/');
    if ($domov !== '' && strpos($cesta, $domov) === 0) $cesta = trim(substr($cesta, strlen($domov)), '/');

    $casti = $cesta === '' ? [] : explode('/', $cesta);
    if (!$casti || count($casti) > 2) return;
    if (!in_array($casti[0], nox_art_route_view_slugs(), true)) return;

    $wp->query_vars = [
        'page_id' => $front,
        'nox_view' => $casti[0],
        'nox_item' => $casti[1] ?? '',
    ];
    unset($wp->query_vars['error']);
}
add_action('parse_request', 'nox_art_route_parse_request', 20);
