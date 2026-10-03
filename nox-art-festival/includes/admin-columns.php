<?php
if (!defined('ABSPATH')) exit;

/**
 * Prehľadové stĺpce v zoznamoch v administrácii, aby bolo hneď vidno väzby
 * (dielo → umelec/miesto, program → dátum/miesto) bez otvárania záznamu.
 */
function nox_art_dielo_columns($columns) {
    $columns['nox_umelec'] = 'Umelec/umelkyňa';
    $columns['nox_miesto'] = 'Miesto';
    return $columns;
}
add_filter('manage_nox_dielo_posts_columns', 'nox_art_dielo_columns');

function nox_art_dielo_column_content($column, $post_id) {
    if ($column === 'nox_umelec') {
        $id = (int) get_post_meta($post_id, '_nox_umelec_id', true);
        echo $id ? esc_html(get_the_title($id)) : '—';
    }
    if ($column === 'nox_miesto') {
        $id = (int) get_post_meta($post_id, '_nox_miesto_id', true);
        echo $id ? esc_html(get_the_title($id)) : '—';
    }
}
add_action('manage_nox_dielo_posts_custom_column', 'nox_art_dielo_column_content', 10, 2);

function nox_art_program_columns($columns) {
    $columns['nox_datum'] = 'Dátum a čas';
    $columns['nox_miesto'] = 'Miesto';
    return $columns;
}
add_filter('manage_nox_program_posts_columns', 'nox_art_program_columns');

function nox_art_program_column_content($column, $post_id) {
    if ($column === 'nox_datum') {
        $datum = get_post_meta($post_id, '_nox_datum', true);
        $od = get_post_meta($post_id, '_nox_cas_od', true);
        $do = get_post_meta($post_id, '_nox_cas_do', true);
        if (!$datum) { echo '—'; return; }
        $out = esc_html($datum);
        if ($od) $out .= ', ' . esc_html($od) . ($do ? '–' . esc_html($do) : '');
        echo $out;
    }
    if ($column === 'nox_miesto') {
        $id = (int) get_post_meta($post_id, '_nox_miesto_id', true);
        echo $id ? esc_html(get_the_title($id)) : '—';
    }
}
add_action('manage_nox_program_posts_custom_column', 'nox_art_program_column_content', 10, 2);

function nox_art_miesto_columns($columns) {
    // Názov miesta je zároveň adresa, preto aj hlavička nesie tento význam.
    if (isset($columns['title'])) $columns['title'] = 'Adresa';
    $columns['nox_priradene'] = 'Priradené';
    $columns['nox_gps'] = 'Súradnice';
    return $columns;
}
add_filter('manage_nox_miesto_posts_columns', 'nox_art_miesto_columns');

/**
 * Načíta naraz všetko, čo je priradené k miestam (dielo, program, podnik),
 * aby sa pri každom riadku zoznamu nerobil vlastný dopyt.
 */
function nox_art_miesto_assignments() {
    static $mapa = null;
    if ($mapa !== null) return $mapa;

    $mapa = [];
    $typy = [
        'nox_dielo' => 'Dielo',
        'nox_program' => 'Sprievodný program',
        'nox_podnik' => 'Podnik',
    ];

    foreach ($typy as $typ => $label) {
        $posts = get_posts([
            'post_type' => $typ,
            'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => [[
                'key' => '_nox_miesto_id',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ]],
        ]);
        foreach ($posts as $p) {
            $miesto_id = (int) get_post_meta($p->ID, '_nox_miesto_id', true);
            if (!$miesto_id) continue;
            $mapa[$miesto_id][] = [
                'id' => $p->ID,
                'nazov' => get_the_title($p) ?: '(bez názvu)',
                'typ' => $label,
            ];
        }
    }

    return $mapa;
}

function nox_art_miesto_column_content($column, $post_id) {
    if ($column === 'nox_priradene') {
        $mapa = nox_art_miesto_assignments();
        $polozky = $mapa[(int) $post_id] ?? [];
        if (!$polozky) { echo '<span style="color:#787c82">—</span>'; return; }

        echo '<ul style="margin:0;list-style:none">';
        foreach ($polozky as $polozka) {
            printf(
                '<li style="margin:0 0 2px"><a href="%s">%s</a> <span style="color:#787c82">· %s</span></li>',
                esc_url(get_edit_post_link($polozka['id'])),
                esc_html($polozka['nazov']),
                esc_html($polozka['typ'])
            );
        }
        echo '</ul>';
    }
    if ($column === 'nox_gps') {
        $lat = get_post_meta($post_id, '_nox_lat', true);
        $lng = get_post_meta($post_id, '_nox_lng', true);
        echo ($lat !== '' && $lng !== '') ? esc_html($lat . ', ' . $lng) : '<span style="color:#b32d2e">chýbajú</span>';
        if (get_post_meta($post_id, '_nox_mimo_vyrez', true)) {
            echo '<br><span style="color:#787c82">mimo výrezu mapy</span>';
        }
    }
}
add_action('manage_nox_miesto_posts_custom_column', 'nox_art_miesto_column_content', 10, 2);

function nox_art_partner_columns($columns) {
    $columns['nox_logo'] = 'Logo';
    $columns['nox_skupina'] = 'Skupina';
    return $columns;
}
add_filter('manage_nox_partner_posts_columns', 'nox_art_partner_columns');

function nox_art_partner_column_content($column, $post_id) {
    if ($column === 'nox_logo') {
        $url = get_the_post_thumbnail_url($post_id, 'thumbnail');
        echo $url
            ? '<img src="' . esc_url($url) . '" alt="" style="max-width:90px;max-height:46px;object-fit:contain">'
            : '<span style="color:#b32d2e">chýba</span>';
    }
    if ($column === 'nox_skupina') {
        $slug = get_post_meta($post_id, '_nox_partner_skupina', true) ?: 'partner';
        $skupiny = nox_art_partner_skupiny();
        echo esc_html($skupiny[$slug] ?? $skupiny['partner']);
    }
}
add_action('manage_nox_partner_posts_custom_column', 'nox_art_partner_column_content', 10, 2);

/**
 * Umelci: fotka a diela, ktoré majú priradené.
 */
function nox_art_umelec_columns($columns) {
    $nove = [];
    foreach ($columns as $kluc => $label) {
        if ($kluc === 'title') $nove['nox_foto'] = 'Fotka';
        $nove[$kluc] = $label;
        if ($kluc === 'title') $nove['nox_diela'] = 'Diela';
    }
    return $nove;
}
add_filter('manage_nox_umelec_posts_columns', 'nox_art_umelec_columns');

/**
 * Diela podľa umelca načítame naraz, nie dopytom pri každom riadku.
 */
function nox_art_umelec_assignments() {
    static $mapa = null;
    if ($mapa !== null) return $mapa;

    $mapa = [];
    $diela = get_posts([
        'post_type' => 'nox_dielo',
        'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    foreach ($diela as $dielo) {
        $umelec_id = (int) get_post_meta($dielo->ID, '_nox_umelec_id', true);
        if (!$umelec_id) continue;
        $mapa[$umelec_id][] = ['id' => $dielo->ID, 'nazov' => get_the_title($dielo) ?: '(bez názvu)'];
    }
    return $mapa;
}

function nox_art_umelec_column_content($column, $post_id) {
    if ($column === 'nox_foto') {
        echo has_post_thumbnail($post_id)
            ? get_the_post_thumbnail($post_id, [48, 48], ['style' => 'width:48px;height:48px;object-fit:cover;border-radius:50%'])
            : '<span style="color:#b32d2e">chýba</span>';
    }
    if ($column === 'nox_diela') {
        $polozky = nox_art_umelec_assignments()[(int) $post_id] ?? [];
        if (!$polozky) { echo '<span style="color:#787c82">—</span>'; return; }

        echo '<ul style="margin:0;list-style:none">';
        foreach ($polozky as $polozka) {
            printf(
                '<li style="margin:0 0 2px"><a href="%s">%s</a></li>',
                esc_url(get_edit_post_link($polozka['id'])),
                esc_html($polozka['nazov'])
            );
        }
        echo '</ul>';
    }
}
add_action('manage_nox_umelec_posts_custom_column', 'nox_art_umelec_column_content', 10, 2);
