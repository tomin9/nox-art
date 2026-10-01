<?php
if (!defined('ABSPATH')) exit;

function nox_art_add_meta_boxes() {
    add_meta_box('nox_miesto_poloha', 'Poloha', 'nox_art_render_miesto_metabox', 'nox_miesto', 'normal', 'high');
    // Podnik má vlastnú adresu aj súradnice, rovnako ako miesto.
    add_meta_box('nox_dielo_suvislosti', 'Súvislosti diela', 'nox_art_render_dielo_metabox', 'nox_dielo', 'side', 'default');
    add_meta_box('nox_program_termin', 'Miesto', 'nox_art_render_program_metabox', 'nox_program', 'side', 'default');
    add_meta_box('nox_program_termin', 'Miesto', 'nox_art_render_program_metabox', 'nox_podnik', 'side', 'default');
    // Čas má zmysel pri všetkom, nielen pri programe: dielo býva prístupné
    // len vo vymedzených hodinách a podnik má otváracie hodiny.
    add_meta_box('nox_termin', 'Termíny', 'nox_art_render_termin_metabox', 'nox_dielo', 'side', 'default');
    add_meta_box('nox_termin', 'Termíny', 'nox_art_render_termin_metabox', 'nox_program', 'side', 'default');
    add_meta_box('nox_termin', 'Termíny', 'nox_art_render_termin_metabox', 'nox_podnik', 'side', 'default');
    // Poradové číslo na mape a na dlaždici – ručne nastaviteľné, aby si
    // editor vedel určiť trasu festivalu.
    foreach (['nox_dielo', 'nox_program', 'nox_podnik'] as $typ) {
        add_meta_box('nox_cislo', 'Číslo na mape', 'nox_art_render_cislo_metabox', $typ, 'side', 'high');
    }
}
add_action('add_meta_boxes', 'nox_art_add_meta_boxes');

/* -------------------------------------------------------------------------
 * MIESTO – adresa + súradnice (s mini-mapou na klikacie zadanie polohy)
 * ---------------------------------------------------------------------- */
function nox_art_render_miesto_metabox($post) {
    wp_nonce_field('nox_art_save_miesto', 'nox_art_miesto_nonce');
    $lat = get_post_meta($post->ID, '_nox_lat', true);
    $lng = get_post_meta($post->ID, '_nox_lng', true);
    ?>
    <p class="description">Názov miesta je zároveň jeho adresa – zadávaj ju do nadpisu záznamu.</p>
    <p>
        <label><strong>Súradnice</strong></label><br>
        <span class="description">Klikni do mapy pre umiestnenie značky, alebo zadaj súradnice ručne (napr. skopírované z Google Maps).</span>
    </p>
    <div id="nox-admin-picker" style="height:320px;border:1px solid #ddd;margin:8px 0"></div>
    <p style="display:flex;gap:12px">
        <label style="flex:1">Lat<br><input type="text" id="nox_lat" name="nox_lat" class="widefat" value="<?php echo esc_attr($lat); ?>"></label>
        <label style="flex:1">Lng<br><input type="text" id="nox_lng" name="nox_lng" class="widefat" value="<?php echo esc_attr($lng); ?>"></label>
    </p>
    <?php
}

function nox_art_save_miesto($post_id) {
    if (!isset($_POST['nox_art_miesto_nonce']) || !wp_verify_nonce($_POST['nox_art_miesto_nonce'], 'nox_art_save_miesto')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['nox_lat']) && $_POST['nox_lat'] !== '') {
        update_post_meta($post_id, '_nox_lat', (float) $_POST['nox_lat']);
    } else {
        delete_post_meta($post_id, '_nox_lat');
    }
    if (isset($_POST['nox_lng']) && $_POST['nox_lng'] !== '') {
        update_post_meta($post_id, '_nox_lng', (float) $_POST['nox_lng']);
    } else {
        delete_post_meta($post_id, '_nox_lng');
    }
}
add_action('save_post_nox_miesto', 'nox_art_save_miesto');

/* -------------------------------------------------------------------------
 * DIELO – väzba na miesto a umelca (výber z existujúcich záznamov)
 * ---------------------------------------------------------------------- */
function nox_art_render_dielo_metabox($post) {
    wp_nonce_field('nox_art_save_dielo', 'nox_art_dielo_nonce');
    $miesto_id = get_post_meta($post->ID, '_nox_miesto_id', true);
    $umelec_id = get_post_meta($post->ID, '_nox_umelec_id', true);
    $typ = get_post_meta($post->ID, '_nox_typ', true);

    $miesta = get_posts(['post_type' => 'nox_miesto', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $umelci = get_posts(['post_type' => 'nox_umelec', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>
    <p>
        <label for="nox_miesto_id"><strong>Miesto</strong></label><br>
        <select id="nox_miesto_id" name="nox_miesto_id" class="widefat">
            <option value="">— bez miesta —</option>
            <?php foreach ($miesta as $m): ?>
                <option value="<?php echo esc_attr($m->ID); ?>" <?php selected($miesto_id, $m->ID); ?>><?php echo esc_html(get_the_title($m)); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!$miesta): ?><span class="description">Zatiaľ nemáš vytvorené žiadne miesto.</span><?php endif; ?>
    </p>
    <p>
        <label for="nox_umelec_id"><strong>Umelec/umelkyňa</strong></label><br>
        <select id="nox_umelec_id" name="nox_umelec_id" class="widefat">
            <option value="">— bez umelca —</option>
            <?php foreach ($umelci as $u): ?>
                <option value="<?php echo esc_attr($u->ID); ?>" <?php selected($umelec_id, $u->ID); ?>><?php echo esc_html(get_the_title($u)); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!$umelci): ?><span class="description">Zatiaľ nemáš vytvoreného žiadneho umelca.</span><?php endif; ?>
    </p>
    <p>
        <label for="nox_typ"><strong>Typ diela</strong></label><br>
        <input type="text" id="nox_typ" name="nox_typ" class="widefat" value="<?php echo esc_attr($typ); ?>" placeholder="napr. Projekcia / fasáda">
        <span class="description">Voľný text, zobrazí sa ako štítok na karte diela (nepovinné).</span>
    </p>
    <?php
}

function nox_art_save_dielo($post_id) {
    if (!isset($_POST['nox_art_dielo_nonce']) || !wp_verify_nonce($_POST['nox_art_dielo_nonce'], 'nox_art_save_dielo')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_nox_miesto_id', isset($_POST['nox_miesto_id']) ? absint($_POST['nox_miesto_id']) : 0);
    update_post_meta($post_id, '_nox_umelec_id', isset($_POST['nox_umelec_id']) ? absint($_POST['nox_umelec_id']) : 0);
    update_post_meta($post_id, '_nox_typ', isset($_POST['nox_typ']) ? sanitize_text_field($_POST['nox_typ']) : '');
}
add_action('save_post_nox_dielo', 'nox_art_save_dielo');

/* -------------------------------------------------------------------------
 * PROGRAM – dátum, čas, voliteľne miesto
 * ---------------------------------------------------------------------- */
/**
 * Poradové číslo, ktoré sa zobrazuje v kvapke na dlaždici aj v značke na
 * mape. Nevyplnené čísla sa doplnia automaticky – prideľujú sa tie, ktoré
 * ešte nie sú ručne obsadené, takže sa nikdy nezdvojia.
 */
function nox_art_render_cislo_metabox($post) {
    wp_nonce_field('nox_art_save_cislo', 'nox_art_cislo_nonce');
    $cislo = get_post_meta($post->ID, '_nox_cislo', true);
    ?>
    <p>
        <input type="number" id="nox_cislo" name="nox_cislo" class="widefat" min="1" step="1" value="<?php echo esc_attr($cislo); ?>" placeholder="automaticky">
    </p>
    <p class="description">Nechaj prázdne a číslo sa pridelí automaticky podľa poradia.</p>
    <?php
}

function nox_art_save_cislo($post_id) {
    if (!isset($_POST['nox_art_cislo_nonce']) || !wp_verify_nonce($_POST['nox_art_cislo_nonce'], 'nox_art_save_cislo')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $cislo = isset($_POST['nox_cislo']) ? absint($_POST['nox_cislo']) : 0;
    if ($cislo > 0) {
        update_post_meta($post_id, '_nox_cislo', $cislo);
    } else {
        delete_post_meta($post_id, '_nox_cislo');
    }
}
foreach (['nox_dielo', 'nox_program', 'nox_podnik'] as $nox_typ) {
    add_action('save_post_' . $nox_typ, 'nox_art_save_cislo');
}

/**
 * Termíny – festival trvá viac dní, takže každá položka môže mať vlastný
 * deň a čas zvlášť (napr. v piatok 17:00–23:00, v sobotu 10:00–18:00).
 * Ukladajú sa ako jedno pole v meta _nox_terminy.
 */
function nox_art_termin_rows() {
    return 3;   // dva festivalové dni + jeden riadok navyše
}

function nox_art_get_terminy($post_id) {
    $terminy = get_post_meta($post_id, '_nox_terminy', true);
    if (is_array($terminy) && $terminy) return $terminy;

    // Spätná kompatibilita so starším jedným termínom.
    $datum = get_post_meta($post_id, '_nox_datum', true);
    $od = get_post_meta($post_id, '_nox_cas_od', true);
    $do = get_post_meta($post_id, '_nox_cas_do', true);
    if ($datum || $od || $do) return [['datum' => $datum, 'od' => $od, 'do' => $do]];

    return [];
}

function nox_art_render_termin_metabox($post) {
    wp_nonce_field('nox_art_save_termin', 'nox_art_termin_nonce');
    $terminy = nox_art_get_terminy($post->ID);
    $rows = max(nox_art_termin_rows(), count($terminy) + 1);
    ?>
    <p class="description" style="margin-top:0">Každý riadok je jeden deň festivalu. Dátum necháš prázdny, ak čas platí počas celého festivalu (napr. otváracie hodiny).</p>
    <?php for ($i = 0; $i < $rows; $i++): $t = $terminy[$i] ?? ['datum' => '', 'od' => '', 'do' => '']; ?>
    <div style="margin:0 0 14px;padding:0 0 12px;border-bottom:1px solid #e0e0e0">
        <input type="date" name="nox_termin_datum[]" class="widefat" value="<?php echo esc_attr($t['datum']); ?>">
        <p style="display:flex;gap:10px;margin:6px 0 0">
            <label style="flex:1">Od<br><input type="time" name="nox_termin_od[]" class="widefat" value="<?php echo esc_attr($t['od']); ?>"></label>
            <label style="flex:1">Do<br><input type="time" name="nox_termin_do[]" class="widefat" value="<?php echo esc_attr($t['do']); ?>"></label>
        </p>
    </div>
    <?php endfor; ?>
    <p>
        <label>
            <input type="checkbox" name="nox_harmonogram_samostatne" value="1" <?php checked(get_post_meta($post->ID, '_nox_samostatne', true)); ?>>
            V harmonograme uviesť samostatne
        </label>
    </p>
    <p class="description">Inak sa položka zlúči s ostatnými zo svojej kategórie do jedného riadku (napr. „Inštalácie 17:00–22:00").</p>
    <?php
}

function nox_art_save_termin($post_id) {
    if (!isset($_POST['nox_art_termin_nonce']) || !wp_verify_nonce($_POST['nox_art_termin_nonce'], 'nox_art_save_termin')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $datumy = (array) ($_POST['nox_termin_datum'] ?? []);
    $od = (array) ($_POST['nox_termin_od'] ?? []);
    $do = (array) ($_POST['nox_termin_do'] ?? []);

    $terminy = [];
    foreach ($datumy as $i => $datum) {
        $row = [
            'datum' => sanitize_text_field(wp_unslash($datum)),
            'od' => sanitize_text_field(wp_unslash($od[$i] ?? '')),
            'do' => sanitize_text_field(wp_unslash($do[$i] ?? '')),
        ];
        // Úplne prázdny riadok preskakujeme, nech sa neukladajú prázdne termíny.
        if ($row['datum'] || $row['od'] || $row['do']) $terminy[] = $row;
    }

    if (!empty($_POST['nox_harmonogram_samostatne'])) {
        update_post_meta($post_id, '_nox_samostatne', 1);
    } else {
        delete_post_meta($post_id, '_nox_samostatne');
    }

    if ($terminy) {
        update_post_meta($post_id, '_nox_terminy', $terminy);
    } else {
        delete_post_meta($post_id, '_nox_terminy');
    }

    // Staršie polia držíme zosynchronizované s prvým termínom, aby na ne
    // mohol zvyšok pluginu aj naďalej siahať.
    $prvy = $terminy[0] ?? ['datum' => '', 'od' => '', 'do' => ''];
    update_post_meta($post_id, '_nox_datum', $prvy['datum']);
    update_post_meta($post_id, '_nox_cas_od', $prvy['od']);
    update_post_meta($post_id, '_nox_cas_do', $prvy['do']);
}
add_action('save_post_nox_dielo', 'nox_art_save_termin');
add_action('save_post_nox_podnik', 'nox_art_save_termin');
add_action('save_post_nox_program', 'nox_art_save_termin');

/**
 * Pri bode programu zostáva v tomto boxe len výber miesta – dátum a časy
 * rieši spoločný box "Termíny", ktorý zvládne aj viac dní naraz.
 */
function nox_art_render_program_metabox($post) {
    wp_nonce_field('nox_art_save_program', 'nox_art_program_nonce');
    $miesto_id = get_post_meta($post->ID, '_nox_miesto_id', true);
    $miesta = get_posts(['post_type' => 'nox_miesto', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>
    <p>
        <label for="nox_miesto_id"><strong>Miesto (nepovinné)</strong></label><br>
        <select id="nox_miesto_id" name="nox_miesto_id" class="widefat">
            <option value="">— bez miesta —</option>
            <?php foreach ($miesta as $m): ?>
                <option value="<?php echo esc_attr($m->ID); ?>" <?php selected($miesto_id, $m->ID); ?>><?php echo esc_html(get_the_title($m)); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function nox_art_save_program($post_id) {
    if (!isset($_POST['nox_art_program_nonce']) || !wp_verify_nonce($_POST['nox_art_program_nonce'], 'nox_art_save_program')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_nox_miesto_id', isset($_POST['nox_miesto_id']) ? absint($_POST['nox_miesto_id']) : 0);
}
add_action('save_post_nox_program', 'nox_art_save_program');
add_action('save_post_nox_podnik', 'nox_art_save_program');

/* -------------------------------------------------------------------------
 * Klikacia mini-mapa v administrácii pre výber súradníc miesta (Leaflet).
 * ---------------------------------------------------------------------- */
function nox_art_admin_enqueue($hook) {
    global $post_type;
    if (!in_array($hook, ['post.php', 'post-new.php'], true) || $post_type !== 'nox_miesto') return;

    wp_enqueue_style('nox-art-leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_script('nox-art-leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

    wp_add_inline_script('nox-art-leaflet-js', nox_art_asset('admin-picker.js'));
}
add_action('admin_enqueue_scripts', 'nox_art_admin_enqueue');
