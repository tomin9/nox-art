<?php
if (!defined('ABSPATH')) exit;

/**
 * Prenos obsahu medzi dvoma webmi (Nástroje → Exportovať / Importovať).
 *
 * Väzby medzi záznamami držíme ako ID (_nox_miesto_id, _nox_umelec_id).
 * Importér WordPressu prideľuje novým záznamom nové ID, takže by väzby po
 * prenose ukazovali na cudzie (alebo neexistujúce) záznamy. Preto si počas
 * importu poznačíme, ktoré staré ID sa stalo ktorým novým, a na konci
 * importu tieto polia prepíšeme.
 */

/**
 * Meta polia, v ktorých je uložené ID iného záznamu.
 */
function nox_art_import_id_meta_keys() {
    return ['_nox_miesto_id', '_nox_umelec_id'];
}

function nox_art_import_remember_id($post_id, $original_id) {
    if (!$post_id || !$original_id) return;
    $mapa = get_option('nox_art_import_map', []);
    if (!is_array($mapa)) $mapa = [];
    $mapa[(int) $original_id] = (int) $post_id;
    update_option('nox_art_import_map', $mapa, false);
}
add_action('wp_import_insert_post', 'nox_art_import_remember_id', 10, 2);

/**
 * Po importe prepíšeme staré ID v našich väzbách na nové.
 */
function nox_art_import_remap_ids() {
    $mapa = get_option('nox_art_import_map', []);
    if (!is_array($mapa) || !$mapa) return;

    $posts = get_posts([
        'post_type' => ['nox_dielo', 'nox_program', 'nox_podnik'],
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($posts as $post_id) {
        foreach (nox_art_import_id_meta_keys() as $kluc) {
            $stare = (int) get_post_meta($post_id, $kluc, true);
            if (!$stare || !isset($mapa[$stare])) continue;
            update_post_meta($post_id, $kluc, (int) $mapa[$stare]);
        }
    }

    delete_option('nox_art_import_map');
}
add_action('import_end', 'nox_art_import_remap_ids');
