<?php
if (!defined('ABSPATH')) exit;

/**
 * Spoločná kategória pre Diela aj Program – podľa nej sa na stránke filtruje
 * (Všetky / Inštalácie / Nové sgrafitá / Živé sgrafitá / Galéria ulice /
 * Sprievodný program). Je to obyčajná WP taxonómia, takže kategórie sa dajú
 * kedykoľvek premenovať, doplniť alebo prehádzať v administrácii bez zásahu
 * do kódu – filtre na stránke sa prispôsobia samy.
 */

/**
 * Kategórie, ktoré sa založia pri prvom spustení. Poradie tu určuje aj
 * poradie filtrov na stránke (ukladá sa do term_order cez menu_order termu).
 */
function nox_art_default_categories() {
    return ['Inštalácie', 'Nové sgrafitá', 'Živé sgrafitá', 'Galéria ulice', 'Sprievodný program'];
}

function nox_art_register_taxonomy() {
    register_taxonomy('nox_kategoria', ['nox_dielo', 'nox_program'], [
        'labels' => [
            'name' => 'Kategórie',
            'singular_name' => 'Kategória',
            'add_new_item' => 'Pridať kategóriu',
            'edit_item' => 'Upraviť kategóriu',
            'all_items' => 'Kategórie',
            'menu_name' => 'Kategórie',
            'not_found' => 'Žiadne kategórie',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'nox-art-festival',
        'show_admin_column' => true,
        // Hierarchická => v editore sa zobrazí zoznam so zaškrtávacími
        // políčkami (pevná sada kategórií) namiesto voľného písania štítkov.
        'hierarchical' => true,
        'rewrite' => false,
    ]);
}
add_action('init', 'nox_art_register_taxonomy', 5);

/**
 * Prvé spustenie: založí základné kategórie. Beží raz – ak ich editor
 * neskôr premenuje alebo zmaže, plugin mu ich nebude vracať späť.
 */
function nox_art_seed_categories() {
    if (get_option('nox_art_categories_seeded')) return;

    foreach (nox_art_default_categories() as $i => $name) {
        if (!term_exists($name, 'nox_kategoria')) {
            wp_insert_term($name, 'nox_kategoria');
        }
    }
    update_option('nox_art_categories_seeded', 1);
}
add_action('init', 'nox_art_seed_categories', 20);

/**
 * Kategórie daného obsahu (slugy) – používa sa pri filtrovaní na stránke.
 */
function nox_art_post_categories($post_id) {
    $terms = get_the_terms($post_id, 'nox_kategoria');
    if (!$terms || is_wp_error($terms)) return [];
    return array_values(array_map(function($t){ return $t->slug; }, $terms));
}

/**
 * Kategórie pre filtrovacie tlačidlá nad zoznamom. Vracia len tie, ktoré
 * naozaj niečo obsahujú – prázdny filter by používateľa len mýlil.
 * Prijíma jeden typ obsahu aj viac naraz (Program a Diela sú na stránke
 * zlúčené do jedného zoznamu so spoločnými filtrami).
 */
function nox_art_filter_terms($post_type) {
    $terms = get_terms([
        'taxonomy' => 'nox_kategoria',
        'hide_empty' => false,
        'orderby' => 'term_id',
        'order' => 'ASC',
    ]);
    if (!$terms || is_wp_error($terms)) return [];

    $used = [];
    foreach (get_posts(['post_type' => (array) $post_type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
        foreach (nox_art_post_categories($id) as $slug) $used[$slug] = true;
    }

    return array_values(array_filter($terms, function($t) use ($used) {
        return isset($used[$t->slug]);
    }));
}
