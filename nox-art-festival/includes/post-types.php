<?php
if (!defined('ABSPATH')) exit;

/**
 * Štyri jednoduché custom post types tvoria celý dátový model festivalu:
 * Miesta (kde sa dá dielo vidieť), Diela (popisky), Umelci a Program.
 * Väzby medzi nimi (dielo↔miesto, dielo↔umelec, program↔miesto) sú len ID
 * uložené v post meta – žiadna vlastná DB tabuľka, žiadna taxonómia netreba.
 */
function nox_art_register_post_types() {

    register_post_type('nox_miesto', [
        'labels' => [
            'name' => 'Miesta',
            'singular_name' => 'Miesto',
            'add_new_item' => 'Pridať nové miesto',
            'edit_item' => 'Upraviť miesto',
            'all_items' => 'Miesta',
            'menu_name' => 'NOX:ART',
            'not_found' => 'Žiadne miesta',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'nox-art-festival',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-location',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);

    register_post_type('nox_umelec', [
        'labels' => [
            'name' => 'Umelci',
            'singular_name' => 'Umelec/umelkyňa',
            'add_new_item' => 'Pridať nového umelca/umelkyňu',
            'edit_item' => 'Upraviť umelca/umelkyňu',
            'all_items' => 'Umelci',
            'not_found' => 'Žiadni umelci',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'nox-art-festival',
        'supports' => ['title', 'editor', 'thumbnail'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);

    register_post_type('nox_dielo', [
        'labels' => [
            'name' => 'Diela',
            'singular_name' => 'Dielo',
            'add_new_item' => 'Pridať nové dielo',
            'edit_item' => 'Upraviť dielo',
            'all_items' => 'Diela',
            'not_found' => 'Žiadne diela',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'nox-art-festival',
        'supports' => ['title', 'editor', 'thumbnail'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);

    register_post_type('nox_program', [
        'labels' => [
            'name' => 'Program',
            'singular_name' => 'Bod programu',
            'add_new_item' => 'Pridať bod programu',
            'edit_item' => 'Upraviť bod programu',
            'all_items' => 'Program',
            'not_found' => 'Program je zatiaľ prázdny',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'nox-art-festival',
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'nox_art_register_post_types');

/**
 * Vlastné hlavné menu "NOX:ART" (namiesto rozhádzania štyroch CPT po celom
 * ľavom menu) – prvá podpoložka je zoznam Miest, čo slúži aj ako menu slug.
 */
function nox_art_admin_menu() {
    add_menu_page(
        'NOX:ART',
        'NOX:ART',
        'edit_posts',
        'nox-art-festival',
        'nox_art_admin_landing',
        'dashicons-palmtree',
        26
    );
}
add_action('admin_menu', 'nox_art_admin_menu');

function nox_art_admin_landing() {
    ?>
    <div class="wrap">
        <h1>NOX:ART Festival</h1>
        <p>Obsah podstránky festivalu spravuješ cez položky nižšie v menu: <strong>Miesta</strong>, <strong>Diela</strong>, <strong>Umelci</strong> a <strong>Program</strong>.</p>
        <p>Na stránku, kde chceš zobraziť interaktívnu mapu a zoznamy, vlož shortcode:</p>
        <p><code>[nox_art]</code></p>
    </div>
    <?php
}

/**
 * add_menu_page si vytvorí vlastnú podpoložku s rovnakým slugom ako menu
 * (duplicitná "NOX:ART" položka) – premenujeme ju na niečo zmysluplnejšie.
 */
function nox_art_admin_submenu() {
    global $submenu;
    if (isset($submenu['nox-art-festival'][0])) {
        $submenu['nox-art-festival'][0][0] = 'Prehľad';
    }
}
add_action('admin_menu', 'nox_art_admin_submenu', 100);
