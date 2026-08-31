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
    $columns['nox_adresa'] = 'Adresa';
    $columns['nox_gps'] = 'Súradnice';
    return $columns;
}
add_filter('manage_nox_miesto_posts_columns', 'nox_art_miesto_columns');

function nox_art_miesto_column_content($column, $post_id) {
    if ($column === 'nox_adresa') {
        echo esc_html(get_post_meta($post_id, '_nox_adresa', true) ?: '—');
    }
    if ($column === 'nox_gps') {
        $lat = get_post_meta($post_id, '_nox_lat', true);
        $lng = get_post_meta($post_id, '_nox_lng', true);
        echo ($lat !== '' && $lng !== '') ? esc_html($lat . ', ' . $lng) : '<span style="color:#b32d2e">chýbajú</span>';
    }
}
add_action('manage_nox_miesto_posts_custom_column', 'nox_art_miesto_column_content', 10, 2);
