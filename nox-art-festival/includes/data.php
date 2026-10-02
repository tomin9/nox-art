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
        'podniky' => nox_art_data_podniky(),
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
            // Názov miesta je v praxi adresa – staršie samostatné pole
            // ostáva len ako doplnok, keď je vyplnené.
            'adresa' => get_post_meta($p->ID, '_nox_adresa', true) ?: get_the_title($p),
            'lat' => $lat !== '' ? (float) $lat : null,
            'lng' => $lng !== '' ? (float) $lng : null,
            // Vzdialené miesto, ktoré nemá naťahovať výrez mapy.
            'mimoVyrez' => (bool) get_post_meta($p->ID, '_nox_mimo_vyrez', true),
        ];
    }, $posts);
}

/**
 * Podniky s festivalovým menu – vlastný typ obsahu; polohu si berú z Miesta,
 * ktoré im editor vyberie.
 */
function nox_art_data_podniky() {
    $posts = get_posts(['post_type' => 'nox_podnik', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    return array_map(function($p){
        return [
            'id' => $p->ID,
            'nazov' => get_the_title($p),
            'popis' => apply_filters('the_content', $p->post_content),
            'foto' => get_the_post_thumbnail_url($p->ID, 'large') ?: '',
            // ID prílohy: šablóna z neho vyskladá responzívny <img> so srcset.
            'fotoId' => (int) get_post_thumbnail_id($p->ID),
            'kategorie' => nox_art_post_categories($p->ID),
            'terminy' => nox_art_get_terminy($p->ID),
            'samostatne' => (bool) get_post_meta($p->ID, '_nox_samostatne', true),
            'cislo' => (int) get_post_meta($p->ID, '_nox_cislo', true),
            // Podnik stojí vždy na niektorom z Miest – vlastné súradnice
            // nemá, adresu si berie z neho.
            'miestoId' => (int) get_post_meta($p->ID, '_nox_miesto_id', true) ?: null,
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
            // ID prílohy: šablóna z neho vyskladá responzívny <img> so srcset.
            'fotoId' => (int) get_post_thumbnail_id($p->ID),
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
            // ID prílohy: šablóna z neho vyskladá responzívny <img> so srcset.
            'fotoId' => (int) get_post_thumbnail_id($p->ID),
            'umelecId' => (int) get_post_meta($p->ID, '_nox_umelec_id', true) ?: null,
            'miestoId' => (int) get_post_meta($p->ID, '_nox_miesto_id', true) ?: null,
            'typ' => get_post_meta($p->ID, '_nox_typ', true) ?: '',
            'kategorie' => nox_art_post_categories($p->ID),
            'cislo' => (int) get_post_meta($p->ID, '_nox_cislo', true),
            'terminy' => nox_art_get_terminy($p->ID),
            'samostatne' => (bool) get_post_meta($p->ID, '_nox_samostatne', true),
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
            'cislo' => (int) get_post_meta($p->ID, '_nox_cislo', true),
            'terminy' => nox_art_get_terminy($p->ID),
            'samostatne' => (bool) get_post_meta($p->ID, '_nox_samostatne', true),
            'miestoId' => (int) get_post_meta($p->ID, '_nox_miesto_id', true) ?: null,
            'foto' => get_the_post_thumbnail_url($p->ID, 'large') ?: '',
            // ID prílohy: šablóna z neho vyskladá responzívny <img> so srcset.
            'fotoId' => (int) get_post_thumbnail_id($p->ID),
            'kategorie' => nox_art_post_categories($p->ID),
        ];
    }, $posts);
    usort($items, function($a, $b){
        $ka = ($a['terminy'][0]['datum'] ?? '') . ($a['terminy'][0]['od'] ?? '');
        $kb = ($b['terminy'][0]['datum'] ?? '') . ($b['terminy'][0]['od'] ?? '');
        return strcmp($ka, $kb);
    });
    return $items;
}

/**
 * Partneri zoradení do skupín (organizátor, generálny partner, partneri,
 * mediálni partneri). Vracia len skupiny, v ktorých niekto je.
 */
function nox_art_data_partneri() {
    $posts = get_posts([
        'post_type' => 'nox_partner',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    ]);

    $skupiny = [];
    foreach (nox_art_partner_skupiny() as $slug => $label) $skupiny[$slug] = ['nazov' => $label, 'polozky' => []];

    foreach ($posts as $p) {
        $slug = get_post_meta($p->ID, '_nox_partner_skupina', true) ?: 'partner';
        if (!isset($skupiny[$slug])) $slug = 'partner';
        $skupiny[$slug]['polozky'][] = [
            'id' => $p->ID,
            'nazov' => get_the_title($p),
            'logo' => get_the_post_thumbnail_url($p->ID, 'large') ?: '',
            'url' => get_post_meta($p->ID, '_nox_partner_url', true) ?: '',
        ];
    }

    return array_filter($skupiny, fn($s) => (bool) $s['polozky']);
}
