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

/**
 * Farba značky na mape (a kvapky na dlaždici) pre každú kategóriu.
 * Prednastavené sú odtiene z festivalovej palety – Inštalácie si držia
 * pôvodnú ružovo-oranžovú, ostatné sa od nej líšia, aby bolo na mape na
 * prvý pohľad vidieť, o aký typ obsahu ide. V administrácii sa dá každej
 * kategórii nastaviť vlastná farba, inak sa priradí ďalšia z palety.
 */
function nox_art_color_palette() {
    return [
        'linear-gradient(135deg, #ff2d87, #ff5c3d)', // ružovo-oranžová (Inštalácie)
        'linear-gradient(135deg, #ffb627, #ff7a1a)', // jantárová
        'linear-gradient(135deg, #a05cff, #ff2d87)', // fialovo-ružová
        'linear-gradient(135deg, #16c2a3, #0ea5b7)', // tyrkysová
        'linear-gradient(135deg, #4f8bff, #7b5cff)', // modro-fialová
        'linear-gradient(135deg, #ff5c3d, #ffb627)', // oranžová
        'linear-gradient(135deg, #e0567f, #a05cff)', // tlmená ružová
    ];
}

/**
 * CSS hodnota pozadia značky pre daný term. Vlastná farba z administrácie
 * má prednosť; inak sa použije farba z palety podľa poradia kategórie.
 */
function nox_art_term_color($term) {
    $custom = get_term_meta($term->term_id, '_nox_farba', true);
    if ($custom) return 'linear-gradient(135deg, ' . $custom . ', ' . $custom . ')';

    $palette = nox_art_color_palette();
    $order = array_search($term->term_id, nox_art_term_order(), true);
    if ($order === false) $order = 0;
    return $palette[$order % count($palette)];
}

/**
 * Poradie kategórií (podľa ID, teda podľa vzniku) – podľa neho sa prideľujú
 * farby z palety, aby bola farba kategórie stabilná aj po premenovaní.
 */
function nox_art_term_order() {
    static $order = null;
    if ($order === null) {
        $terms = get_terms(['taxonomy' => 'nox_kategoria', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC', 'fields' => 'ids']);
        $order = (!$terms || is_wp_error($terms)) ? [] : array_map('intval', $terms);
    }
    return $order;
}

/**
 * Mapa slug → farba, posiela sa do JS pre značky na mape.
 */
function nox_art_category_colors() {
    // Volá sa pri každej dlaždici, preto sa výsledok drží v pamäti počas
    // celého requestu – inak by to bol jeden dopyt do DB na dlaždicu.
    static $colors = null;
    if ($colors !== null) return $colors;

    $terms = get_terms(['taxonomy' => 'nox_kategoria', 'hide_empty' => false]);
    if (!$terms || is_wp_error($terms)) return $colors = [];

    $colors = [];
    foreach ($terms as $term) $colors[$term->slug] = nox_art_term_color($term);
    return $colors;
}

/**
 * Políčko s farbou v administrácii kategórie (pridanie aj úprava).
 */
function nox_art_term_color_add_field() {
    ?>
    <div class="form-field">
        <label for="nox_farba">Farba značky na mape</label>
        <input type="color" name="nox_farba" id="nox_farba" value="#ff2d87">
        <label><input type="checkbox" name="nox_farba_auto" value="1" checked> Použiť farbu z palety</label>
        <p>Farba značiek tejto kategórie na mape a kvapky na dlaždici. Pri zaškrtnutom políčku sa priradí automaticky podľa poradia kategórie.</p>
    </div>
    <?php
}
add_action('nox_kategoria_add_form_fields', 'nox_art_term_color_add_field');

function nox_art_term_color_edit_field($term) {
    $value = get_term_meta($term->term_id, '_nox_farba', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="nox_farba">Farba značky na mape</label></th>
        <td>
            <input type="color" name="nox_farba" id="nox_farba" value="<?php echo esc_attr($value ?: '#ff2d87'); ?>">
            <label><input type="checkbox" name="nox_farba_auto" value="1" <?php checked(!$value); ?>> Použiť farbu z palety</label>
            <p class="description">Pri zaškrtnutom políčku sa farba priradí automaticky podľa poradia kategórie.</p>
        </td>
    </tr>
    <?php
}
add_action('nox_kategoria_edit_form_fields', 'nox_art_term_color_edit_field');

function nox_art_save_term_color($term_id) {
    // Oprávnenie overuje WP ešte pred týmto hookom (edit_term capability),
    // tu stačí uložiť hodnotu v očakávanom tvare #rrggbb.
    if (!empty($_POST['nox_farba_auto'])) {
        delete_term_meta($term_id, '_nox_farba');
        return;
    }
    if (!isset($_POST['nox_farba'])) return;

    $color = sanitize_hex_color(wp_unslash($_POST['nox_farba']));
    if ($color) {
        update_term_meta($term_id, '_nox_farba', $color);
    } else {
        delete_term_meta($term_id, '_nox_farba');
    }
}
add_action('created_nox_kategoria', 'nox_art_save_term_color');
add_action('edited_nox_kategoria', 'nox_art_save_term_color');

/**
 * Farba prvej kategórie položky – pre kvapku na dlaždici.
 */
function nox_art_item_color($slugs) {
    if (!$slugs) return '';
    $colors = nox_art_category_colors();
    foreach ((array) $slugs as $slug) {
        if (isset($colors[$slug])) return $colors[$slug];
    }
    return '';
}
