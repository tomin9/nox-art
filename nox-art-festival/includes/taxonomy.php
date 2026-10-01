<?php
if (!defined('ABSPATH')) exit;

/**
 * Spoločná kategória pre Diela, Program aj Miesta – podľa nej sa filtruje
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
    return [
        'Diela' => ['Inštalácie', 'Nové sgrafitá', 'Živé sgrafitá', 'Galéria ulice'],
        'Sprievodný program' => [],
        'Partnerské podniky' => [],
    ];
}

function nox_art_register_taxonomy() {
    register_taxonomy('nox_kategoria', ['nox_dielo', 'nox_program', 'nox_miesto'], [
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
    // Verzia, nie len príznak: keď sa základná sada zmení, doplnia sa aj na
    // stránkach, kde už zakladanie raz prebehlo. Nič sa nemaže – kategórie,
    // ktoré editor nepotrebuje, si zmaže sám.
    if ((int) get_option('nox_art_categories_seeded') >= 4) return;

    // Premenovanie z prvej verzie: skupina sa pôvodne volala "Podniky".
    $stare = get_term_by('name', 'Podniky', 'nox_kategoria');
    if ($stare && !term_exists('Partnerské podniky', 'nox_kategoria')) {
        wp_update_term($stare->term_id, 'nox_kategoria', ['name' => 'Partnerské podniky']);
    }

    foreach (nox_art_default_categories() as $name => $children) {
        $parent = term_exists($name, 'nox_kategoria');
        if (!$parent) $parent = wp_insert_term($name, 'nox_kategoria');
        if (is_wp_error($parent)) continue;
        $parent_id = (int) (is_array($parent) ? $parent['term_id'] : $parent);

        foreach ($children as $child) {
            $existing = get_term_by('name', $child, 'nox_kategoria');
            if (!$existing) {
                wp_insert_term($child, 'nox_kategoria', ['parent' => $parent_id]);
            } elseif ((int) $existing->parent === 0) {
                // Z predchádzajúcej verzie zostali tieto kategórie ako
                // samostatné skupiny – zaradíme ich pod Diela, inak by sa
                // hlavná skupina vôbec neukázala.
                wp_update_term($existing->term_id, 'nox_kategoria', ['parent' => $parent_id]);
            }
        }
    }
    update_option('nox_art_categories_seeded', 4);
}
add_action('init', 'nox_art_seed_categories', 20);

/**
 * Kategórie daného obsahu (slugy) – používa sa pri filtrovaní na stránke.
 */
function nox_art_post_categories($post_id) {
    $terms = get_the_terms($post_id, 'nox_kategoria');
    if (!$terms || is_wp_error($terms)) return [];

    // Pridávame aj nadradené kategórie: dielo označené ako "Inštalácie" musí
    // vyjsť aj pod filtrom "Diela", inak by sa pri hlavnom filtri stratilo.
    $slugs = [];
    foreach ($terms as $term) {
        $slugs[$term->slug] = true;
        foreach (get_ancestors($term->term_id, 'nox_kategoria', 'taxonomy') as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'nox_kategoria');
            if ($ancestor && !is_wp_error($ancestor)) $slugs[$ancestor->slug] = true;
        }
    }
    return array_keys($slugs);
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
 * Filtre v dvoch úrovniach: hlavné skupiny (Diela, Sprievodný program,
 * Podniky) a pod každou jej podkategórie, ktoré sa odkryjú až po jej
 * zvolení. Vracia pole [term, children] len s tým, čo naozaj má obsah.
 */
function nox_art_filter_tree($post_type) {
    // Hlavné skupiny sa zobrazujú vždy, aj keď sú zatiaľ prázdne – sú to
    // pevné piliere stránky a návštevník má hneď vidieť, čo festival ponúka.
    // Podkategórie sa naopak ukážu, až keď v nich niečo je.
    $groups = get_terms(['taxonomy' => 'nox_kategoria', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'term_id', 'order' => 'ASC']);
    if (!$groups || is_wp_error($groups)) return [];

    $used = nox_art_filter_terms($post_type);
    $by_parent = [];
    foreach ($used as $term) {
        if ((int) $term->parent) $by_parent[(int) $term->parent][] = $term;
    }

    // Poradie: najprv základné skupiny tak, ako sú definované, potom ostatné.
    $order = array_keys(nox_art_default_categories());
    usort($groups, function($a, $b) use ($order) {
        $ia = array_search($a->name, $order, true);
        $ib = array_search($b->name, $order, true);
        if ($ia === false) $ia = count($order) + $a->term_id;
        if ($ib === false) $ib = count($order) + $b->term_id;
        return $ia <=> $ib;
    });

    // Podkategórie zoradíme rovnako, ako idú položky v zozname – filtre tak
    // kopírujú poradie obsahu.
    $poradie = function_exists('nox_art_site_category_order') ? nox_art_site_category_order() : [];
    $sort_children = function($terms) use ($poradie) {
        usort($terms, function($a, $b) use ($poradie) {
            $ia = array_search($a->slug, $poradie, true);
            $ib = array_search($b->slug, $poradie, true);
            if ($ia === false) $ia = count($poradie) + $a->term_id;
            if ($ib === false) $ib = count($poradie) + $b->term_id;
            return $ia <=> $ib;
        });
        return $terms;
    };

    $tree = [];
    foreach ($groups as $term) {
        $children = $by_parent[$term->term_id] ?? [];
        $tree[] = ['term' => $term, 'children' => $children ? $sort_children($children) : []];
    }
    return $tree;
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
function nox_art_term_color($term, $depth = 0) {
    $custom = get_term_meta($term->term_id, '_nox_farba', true);
    if ($custom) return 'linear-gradient(135deg, ' . $custom . ', ' . $custom . ')';

    /* Podkategória dedí farbu svojej skupiny: všetky diela majú na mape
       rovnakú značku, nech sú to inštalácie alebo sgrafitá – farba hovorí,
       do ktorej z troch skupín vec patrí, nie o aký druh diela ide.
       (Vlastná farba nastavená v administrácii má aj tak prednosť.) */
    if ($term->parent && $depth < 5) {
        $parent = get_term($term->parent, 'nox_kategoria');
        if ($parent && !is_wp_error($parent)) return nox_art_term_color($parent, $depth + 1);
    }

    // Základné kategórie majú farbu viazanú na slug, nie na poradie – inak by
    // sa im farba posunula len preto, že pribudla iná kategória.
    $fixed = [
        'diela' => 'linear-gradient(135deg, #ff2d87, #ff5c3d)',
        'instalacie' => 'linear-gradient(135deg, #ff2d87, #ff5c3d)',
        'sprievodny-program' => 'linear-gradient(135deg, #4f8bff, #7b5cff)',
        'podniky' => 'linear-gradient(135deg, #ffb627, #ff7a1a)',
        'partnerske-podniky' => 'linear-gradient(135deg, #ffb627, #ff7a1a)',
    ];
    if (isset($fixed[$term->slug])) return $fixed[$term->slug];

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

/**
 * Názov najkonkrétnejšej kategórie položky – podkategória má prednosť pred
 * skupinou, do ktorej patrí. Podľa nej sa v harmonograme zlučujú riadky.
 */
function nox_art_item_category_label($slugs) {
    if (!$slugs) return '';

    $terms = get_terms(['taxonomy' => 'nox_kategoria', 'hide_empty' => false, 'slug' => (array) $slugs]);
    if (!$terms || is_wp_error($terms)) return '';

    $best = null;
    foreach ($terms as $term) {
        // Podkategória (má rodiča) vyhráva nad skupinou.
        if (!$best || ((int) $term->parent && !(int) $best->parent)) $best = $term;
    }
    return $best ? $best->name : '';
}
