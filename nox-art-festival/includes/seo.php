<?php
if (!defined('ABSPATH')) exit;

/**
 * Vyhľadávače: každá adresa festivalovej stránky (/program/, /o-festivale/,
 * /partneri/, /diela/, /diela/the-moon/ …) dostane vlastný titulok, popis,
 * kanonický odkaz a sociálne náhľady. Do kódu stránky pribudne aj štruktúrovaný
 * zápis udalosti (schema.org Event), ktorý Google používa pri výsledkoch
 * o podujatiach. Adresy sekcií sa dopíšu aj do sitemapy WordPressu.
 */

define('NOX_ART_SEO_NAZOV', 'NOX:ART 2026');

/**
 * Je to stránka s celým festivalom (program + o festivale + partneri)?
 */
function nox_art_site_seo_je_festival() {
    $sekcie = nox_art_site_sections();
    return in_array('program', $sekcie, true) && count($sekcie) >= 3;
}

/**
 * Titulok, popis a cesta (za základom stránky) pre aktuálnu adresu.
 */
function nox_art_site_seo() {
    $default_title = get_the_title() ?: 'NOX:ART — Sídlisko Píly, Prievidza';
    $default_desc = 'NOX:ART — medzinárodný festival súčasného umenia na sídlisku Píly v Prievidzi, 30.–31. októbra 2026.';
    $seo = ['title' => $default_title, 'description' => $default_desc, 'path' => ''];

    if (!nox_art_site_seo_je_festival()) return $seo;

    $seo['title'] = 'NOX:ART 2026 — festival súčasného umenia, Sídlisko Píly, Prievidza';
    $route = nox_art_route_current();

    if ($route['section']) {
        $def = nox_art_route_sections()[$route['section']];
        $seo['title'] = $def['title'] . ' — ' . NOX_ART_SEO_NAZOV . ', Prievidza';
        $seo['description'] = $def['description'];
        $seo['path'] = $route['section'] . '/';
        return $seo;
    }

    if ($route['view']) {
        $seo['path'] = $route['view'] . '/';
        if ($route['view'] === 'harmonogram') {
            $seo['title'] = 'Časový harmonogram — ' . NOX_ART_SEO_NAZOV . ', Prievidza';
            $seo['description'] = 'Časový harmonogram festivalu NOX:ART na sídlisku Píly v Prievidzi, 30.–31. októbra 2026.';
        } elseif ($term = get_term_by('slug', $route['view'], 'nox_kategoria')) {
            $seo['title'] = $term->name . ' — ' . NOX_ART_SEO_NAZOV . ', Prievidza';
            $seo['description'] = $term->description
                ? wp_strip_all_tags($term->description)
                : $term->name . ' na festivale NOX:ART na sídlisku Píly v Prievidzi, 30.–31. októbra 2026.';
        }

        if ($route['item']) {
            $seo['path'] .= $route['item'] . '/';
            $posts = get_posts([
                'post_type' => ['nox_dielo', 'nox_program', 'nox_podnik'],
                'name' => $route['item'],
                'posts_per_page' => 1,
                'post_status' => 'publish',
            ]);
            if ($posts) {
                $seo['title'] = get_the_title($posts[0]) . ' — ' . NOX_ART_SEO_NAZOV . ', Prievidza';
                $text = wp_strip_all_tags(get_the_excerpt($posts[0]) ?: $posts[0]->post_content);
                if ($text) $seo['description'] = wp_trim_words($text, 30, '…');
            }
        }
    }

    return $seo;
}

/**
 * Kanonický odkaz, Open Graph a štruktúrované dáta do <head>.
 */
function nox_art_site_seo_head($seo) {
    // Kanonický odkaz tvoríme sami (aj pre /program/ a ďalšie adresy, ktoré
    // by WordPress zbalil na adresu stránky).
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'nox_art_festival_canonical', 1);

    $base = nox_art_route_base();
    $url = $base . $seo['path'];
    $obrazok = NOX_ART_URL . 'assets/site/pozadie-1-1600.webp';

    printf('<link rel="canonical" href="%s">' . "\n", esc_url($url));
    printf('<meta property="og:type" content="website">' . "\n");
    printf('<meta property="og:locale" content="sk_SK">' . "\n");
    printf('<meta property="og:site_name" content="NOX:ART">' . "\n");
    printf('<meta property="og:title" content="%s">' . "\n", esc_attr($seo['title']));
    printf('<meta property="og:description" content="%s">' . "\n", esc_attr($seo['description']));
    printf('<meta property="og:url" content="%s">' . "\n", esc_url($url));
    printf('<meta property="og:image" content="%s">' . "\n", esc_url($obrazok));
    printf('<meta name="twitter:card" content="summary_large_image">' . "\n");

    if (!nox_art_site_seo_je_festival()) return;

    $udalost = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => 'NOX:ART 2026',
        'description' => 'Festival súčasného umenia vo verejnom priestore sídliska Píly v Prievidzi.',
        'startDate' => '2026-10-30',
        'endDate' => '2026-10-31',
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'isAccessibleForFree' => true,
        'url' => $base,
        'image' => [$obrazok],
        'inLanguage' => 'sk',
        'location' => [
            '@type' => 'Place',
            'name' => 'Sídlisko Píly',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Prievidza',
                'addressCountry' => 'SK',
            ],
        ],
        'organizer' => [
            '@type' => 'Organization',
            'name' => 'Ars Preuge',
            'url' => $base,
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($udalost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/**
 * Adresy sekcií do sitemapy WordPressu (wp-sitemap.xml) – k stránke s festivalom.
 */
function nox_art_site_seo_sitemap($entries, $post_type) {
    if ($post_type !== 'page') return $entries;

    $id = (int) get_option('page_on_front');
    if (!$id) $id = (int) (function_exists('nox_art_festival_page_id') ? nox_art_festival_page_id() : 0);
    if (!$id || !in_array('program', nox_art_site_sections_for_page($id), true)) return $entries;

    $base = user_trailingslashit(get_permalink($id));
    $base = apply_filters('nox_art_route_base', $base);
    foreach (array_keys(nox_art_route_sections()) as $slug) {
        $entries[] = ['loc' => $base . $slug . '/'];
    }
    return $entries;
}
add_filter('wp_sitemaps_posts_entries', 'nox_art_site_seo_sitemap', 10, 2);
