<?php
if (!defined('ABSPATH')) exit;

/**
 * Poskladá celý obsah festivalu do jedného poľa, ktoré sa na frontende pošle
 * do JS ako vopred vyrenderovaný JSON (wp_localize_script) – žiadne AJAX
 * volania navyše, obsah sa mení len keď editor uloží záznam v adminovi.
 */
function nox_art_build_data() {
    return [
        'miesta' => nox_art_data_miesta(),
        'diela' => nox_art_data_diela(),
        'umelci' => nox_art_data_umelci(),
        'program' => nox_art_data_program(),
    ];
}

function nox_art_data_miesta() {
    $posts = get_posts(['post_type' => 'nox_miesto', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    return array_map(function($p){
        $lat = get_post_meta($p->ID, '_nox_lat', true);
        $lng = get_post_meta($p->ID, '_nox_lng', true);
        return [
            'id' => $p->ID,
            'nazov' => get_the_title($p),
            'adresa' => get_post_meta($p->ID, '_nox_adresa', true) ?: '',
            'lat' => $lat !== '' ? (float) $lat : null,
            'lng' => $lng !== '' ? (float) $lng : null,
            'popis' => apply_filters('the_content', $p->post_content),
            'foto' => get_the_post_thumbnail_url($p->ID, 'large') ?: '',
        ];
    }, $posts);
}

function nox_art_data_umelci() {
    $posts = get_posts(['post_type' => 'nox_umelec', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    return array_map(function($p){
        return [
            'id' => $p->ID,
            'meno' => get_the_title($p),
            'popis' => apply_filters('the_content', $p->post_content),
            'foto' => get_the_post_thumbnail_url($p->ID, 'medium') ?: '',
        ];
    }, $posts);
}

function nox_art_data_diela() {
    $posts = get_posts(['post_type' => 'nox_dielo', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    return array_map(function($p){
        return [
            'id' => $p->ID,
            'nazov' => get_the_title($p),
            'popis' => apply_filters('the_content', $p->post_content),
            'foto' => get_the_post_thumbnail_url($p->ID, 'large') ?: '',
            'umelecId' => (int) get_post_meta($p->ID, '_nox_umelec_id', true) ?: null,
            'miestoId' => (int) get_post_meta($p->ID, '_nox_miesto_id', true) ?: null,
            'typ' => get_post_meta($p->ID, '_nox_typ', true) ?: '',
        ];
    }, $posts);
}

function nox_art_data_program() {
    $posts = get_posts(['post_type' => 'nox_program', 'post_status' => 'publish', 'numberposts' => -1]);
    $items = array_map(function($p){
        return [
            'id' => $p->ID,
            'nazov' => get_the_title($p),
            'popis' => apply_filters('the_content', $p->post_content),
            'datum' => get_post_meta($p->ID, '_nox_datum', true) ?: '',
            'casOd' => get_post_meta($p->ID, '_nox_cas_od', true) ?: '',
            'casDo' => get_post_meta($p->ID, '_nox_cas_do', true) ?: '',
            'miestoId' => (int) get_post_meta($p->ID, '_nox_miesto_id', true) ?: null,
        ];
    }, $posts);
    usort($items, function($a, $b){
        return strcmp($a['datum'] . $a['casOd'], $b['datum'] . $b['casOd']);
    });
    return $items;
}
