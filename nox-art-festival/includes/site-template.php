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
            'miesta' => nox_art_site_map_places(),
            'diela' => nox_art_data_diela(),
            'farby' => nox_art_category_colors(),
            'cisla' => nox_art_site_map_numbers(),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'nox_art_site_enqueue_assets');

/**
 * Jeden spoločný zoznam všetkého, čo sa zobrazuje v sekcii Program:
 * body programu (chronologicky), diela a miesta s priradenou kategóriou
 * (partnerské podniky). Stavia sa tu, a nie v šablóne, lebo to isté
 * číslovanie potrebuje aj mapa – a tá dostáva dáta ešte pred vykreslením.
 *
 * Čísla: ručne zadané majú prednosť, zvyšku sa priradia tie, ktoré ešte
 * nie sú obsadené – vďaka tomu sa dve položky nikdy netrafia na to isté
 * číslo, aj keď editor očísluje len niektoré.
 */
function nox_art_site_items() {
    static $items = null;
    if ($items !== null) return $items;

    $items = [];

    $umelecById = [];
    foreach (nox_art_data_umelci() as $u) $umelecById[$u['id']] = $u;

    $miestoById = [];
    foreach (nox_art_data_miesta() as $m) $miestoById[$m['id']] = $m;

    foreach (nox_art_data_program() as $item) {
        $items[] = [
            'id' => 'program-' . $item['id'],
            'nazov' => $item['nazov'],
            'foto' => $item['foto'],
            'kategorie' => $item['kategorie'],
            'terminy' => $item['terminy'],
            'samostatne' => $item['samostatne'],
            'kategoriaNazov' => nox_art_item_category_label($item['kategorie']),
            'kategoriaSlug' => (nox_art_item_category_term($item['kategorie'])->slug ?? ''),
            'popis' => $item['popis'],
            'meta' => '',
            'work' => '',
            'miestoId' => $item['miestoId'],
            'miestoNazov' => $miestoById[$item['miestoId']]['nazov'] ?? '',
            'cislo' => $item['cislo'],
        ];
    }

    foreach (nox_art_data_diela() as $d) {
        $u = $umelecById[$d['umelecId']] ?? null;
        $items[] = [
            'id' => 'work-' . $d['id'],
            'nazov' => $d['nazov'],
            'foto' => $d['foto'],
            'kategorie' => $d['kategorie'],
            'terminy' => $d['terminy'],
            'samostatne' => $d['samostatne'],
            'kategoriaNazov' => nox_art_item_category_label($d['kategorie']),
            'kategoriaSlug' => (nox_art_item_category_term($d['kategorie'])->slug ?? ''),
            'popis' => $d['popis'],
            'meta' => $u ? $u['meno'] : '',
            'work' => $d['id'],
            'miestoId' => $d['miestoId'],
            'miestoNazov' => $miestoById[$d['miestoId']]['nazov'] ?? '',
            'cislo' => $d['cislo'],
        ];
    }

    // Podniky sú vlastný typ obsahu, takže idú do zoznamu vždy.
    foreach (nox_art_data_podniky() as $p) {
        // Bez zaškrtnutej kategórie by podnik vypadol zo všetkých filtrov –
        // priradíme ho teda aspoň do jeho vlastnej skupiny.
        $kategorie = $p['kategorie'] ?: nox_art_podnik_default_categories();
        $items[] = [
            'id' => 'podnik-' . $p['id'],
            'nazov' => $p['nazov'],
            'foto' => $p['foto'],
            'kategorie' => $kategorie,
            'terminy' => $p['terminy'],
            'samostatne' => $p['samostatne'],
            'kategoriaNazov' => nox_art_item_category_label($kategorie),
            'kategoriaSlug' => (nox_art_item_category_term($kategorie)->slug ?? ''),
            'popis' => $p['popis'],
            'meta' => $p['adresa'],
            'work' => '',
            // Vybrané miesto má prednosť pred vlastnými súradnicami.
            'miestoId' => $p['miestoId'] ?: $p['id'],
            'miestoNazov' => $p['miestoId'] ? ($miestoById[$p['miestoId']]['nazov'] ?? $p['adresa']) : $p['adresa'],
            'cislo' => $p['cislo'],
        ];
    }

    // Miesto sa do zoznamu dostane, len keď má priradenú kategóriu – bežné
    // miesto je nositeľom súradníc pre dielo, nie samostatná položka.
    foreach (nox_art_data_miesta() as $m) {
        if (!$m['kategorie']) continue;
        $items[] = [
            'id' => 'miesto-' . $m['id'],
            'nazov' => $m['nazov'],
            'foto' => $m['foto'],
            'kategorie' => $m['kategorie'],
            'terminy' => $m['terminy'],
            'samostatne' => $m['samostatne'],
            'kategoriaNazov' => nox_art_item_category_label($m['kategorie']),
            'kategoriaSlug' => (nox_art_item_category_term($m['kategorie'])->slug ?? ''),
            'popis' => $m['popis'],
            'meta' => $m['adresa'],
            'work' => '',
            'miestoId' => $m['id'],
            'miestoNazov' => $m['adresa'],
            'cislo' => $m['cislo'],
        ];
    }

    /* Zoradenie podľa kategórie – v tomto poradí sa aj prideľujú čísla, aby
       čísla na mape šli po skupinách a nie krížom cez ne. V rámci kategórie
       zostáva pôvodné poradie (program chronologicky, zvyšok podľa názvu). */
    $poradie = nox_art_site_category_order();
    $rank = function($kategorie) use ($poradie) {
        $best = count($poradie);
        foreach ($kategorie as $slug) {
            $i = array_search($slug, $poradie, true);
            if ($i !== false && $i < $best) $best = $i;
        }
        return $best;
    };
    foreach ($items as $i => &$item) {
        $item['_rank'] = [$rank($item['kategorie']), $i];
    }
    unset($item);
    usort($items, function($a, $b) {
        return $a['_rank'] <=> $b['_rank'];
    });
    foreach ($items as &$item) unset($item['_rank']);
    unset($item);

    $obsadene = [];
    foreach ($items as $item) {
        if ($item['cislo'] > 0) $obsadene[$item['cislo']] = true;
    }

    $dalsie = 1;
    foreach ($items as &$item) {
        if ($item['cislo'] > 0) continue;
        while (isset($obsadene[$dalsie])) $dalsie++;
        $item['cislo'] = $dalsie;
        $obsadene[$dalsie] = true;
    }
    unset($item);

    return $items;
}

/**
 * Položky s vyplneným časom, zoradené podľa dní – podklad pre sekciu
 * Časový harmonogram. Deň "" (prázdny kľúč) znamená, že položka platí
 * počas celého festivalu (vyplnený čas, nevyplnený dátum).
 */
function nox_art_site_schedule() {
    $days = [];
    foreach (nox_art_site_items() as $item) {
        // Každý termín je vlastný riadok – položka na oba dni sa tak objaví
        // v oboch dňoch, s vlastným časom.
        foreach ($item['terminy'] as $t) {
            if (!$t['od'] && !$t['do']) continue;

            /* Diela sa v harmonograme zlučujú po kategóriách: nezaujíma, že
               inštalácií je dvadsať, ale že svietia 17:00–22:00. Bod programu
               je naopak jednotlivá udalosť a položka označená ako "samostatne"
               (napr. výstava otvorená celý deň) má mať vlastný riadok tiež. */
            $jednotlivo = $item['samostatne']
                || strpos($item['id'], 'program-') === 0
                || !$item['kategoriaNazov'];

            $kluc = $jednotlivo
                ? 'i:' . $item['id'] . ':' . $t['od'] . $t['do']
                : 'k:' . $item['kategoriaNazov'] . ':' . $t['od'] . $t['do'];

            if (isset($days[$t['datum']][$kluc])) {
                $days[$t['datum']][$kluc]['pocet']++;
                continue;
            }

            $days[$t['datum']][$kluc] = [
                'nazov' => $jednotlivo ? $item['nazov'] : $item['kategoriaNazov'],
                'meta' => $jednotlivo ? $item['meta'] : '',
                'kategorie' => $item['kategorie'],
                // Na čo riadok odkazuje: jednotlivá položka má detail,
                // zlúčený riadok otvorí svoju kategóriu v zozname.
                'detailId' => $jednotlivo ? $item['id'] : '',
                'kategoriaSlug' => $item['kategoriaSlug'],
                'casOd' => $t['od'],
                'casDo' => $t['do'],
                'pocet' => 1,
                'jednotlivo' => $jednotlivo,
            ];
        }
    }

    // Dni chronologicky, "celý festival" až na koniec; v rámci dňa podľa času.
    uksort($days, function($a, $b) {
        if ($a === '') return 1;
        if ($b === '') return -1;
        return strcmp($a, $b);
    });
    foreach ($days as &$items) {
        $items = array_values($items);
        usort($items, function($a, $b) {
            return strcmp($a['casOd'] . $a['nazov'], $b['casOd'] . $b['nazov']);
        });
    }
    unset($items);

    return $days;
}

/**
 * Poradie kategórií v zozname a v číslovaní. Riadi sa slugmi, takže
 * premenovanie kategórie ho nerozhodí; neznáme kategórie idú na koniec.
 */
function nox_art_site_category_order() {
    return apply_filters('nox_art_site_category_order', [
        'instalacie',
        'nove-sgrafita',
        'galeria-ulice',
        'zive-sgrafita',
        'diela',              // dielo bez podkategórie
        'sprievodny-program',
        'partnerske-podniky',
        'podniky',            // starší slug tej istej skupiny
    ]);
}

/**
 * Body na mape: všetky miesta a k nim podniky, ktoré si žiadne miesto
 * nevybrali a stoja na vlastných súradniciach. ID príspevkov sa medzi typmi
 * obsahu nikdy neopakujú, takže sa kľúče nemôžu pobiť.
 */
function nox_art_site_map_places() {
    $places = nox_art_data_miesta();
    foreach (nox_art_data_podniky() as $podnik) {
        if (!$podnik['miestoId']) $places[] = $podnik;
    }
    return $places;
}

/**
 * Čísla značiek na mape: pre každé miesto číslo prvej položky, ktorá naň
 * ukazuje. Mapa a dlaždice tak ukazujú to isté číslo.
 */
function nox_art_site_map_numbers() {
    $numbers = [];
    foreach (nox_art_site_items() as $item) {
        if ($item['miestoId'] && !isset($numbers[$item['miestoId']])) {
            $numbers[$item['miestoId']] = $item['cislo'];
        }
    }
    return $numbers;
}

/**
 * Popisy jednotlivých termínov, napr. ["Pia 30.10. · 18:00–22:00", …].
 */
function nox_art_site_time_labels($terminy) {
    $labels = [];
    foreach ((array) $terminy as $t) {
        $label = nox_art_site_time_label($t['datum'] ?? '', $t['od'] ?? '', $t['do'] ?? '');
        if ($label) $labels[] = $label;
    }
    return $labels;
}

/**
 * Krátky popis termínu na dlaždicu: "Pia 30.10. · 18:00–22:00". Dátum aj
 * časy sú nepovinné – bez dátumu zostane len čas (otváracie hodiny platné
 * počas celého festivalu), bez času len deň.
 */
function nox_art_site_time_label($datum, $cas_od, $cas_do) {
    $parts = [];

    if ($datum) {
        list($dayName, $dayDate) = nox_art_site_day_label($datum);
        $parts[] = mb_substr($dayName, 0, 3) . ' ' . $dayDate;
    }

    $cas = $cas_od;
    if ($cas_od && $cas_do) $cas = $cas_od . '–' . $cas_do;
    elseif (!$cas_od && $cas_do) $cas = 'do ' . $cas_do;
    if ($cas) $parts[] = $cas;

    return implode(' · ', $parts);
}

/**
 * Pomocné funkcie použité v templates/nox-art-site-template.php.
 */
function nox_art_site_asset($file) {
    return esc_url(NOX_ART_URL . 'assets/site/' . $file);
}

function nox_art_site_day_label($datum) {
    $names = [1 => 'Pondelok', 2 => 'Utorok', 3 => 'Streda', 4 => 'Štvrtok', 5 => 'Piatok', 6 => 'Sobota', 7 => 'Nedeľa'];
    $ts = strtotime($datum);
    if (!$ts) return ['Deň', $datum];
    $n = (int) date('N', $ts);
    // Formát 'j.n.' už bodku na konci má – pridávať ďalšiu dávalo "30.10..".
    return [$names[$n] ?? 'Deň', date('j.n.', $ts)];
}
