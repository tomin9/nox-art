<?php
if (!defined('ABSPATH')) exit;

function nox_art_add_meta_boxes() {
    add_meta_box('nox_miesto_poloha', 'Poloha', 'nox_art_render_miesto_metabox', 'nox_miesto', 'normal', 'high');
    add_meta_box('nox_dielo_suvislosti', 'Súvislosti diela', 'nox_art_render_dielo_metabox', 'nox_dielo', 'side', 'default');
    add_meta_box('nox_program_termin', 'Termín', 'nox_art_render_program_metabox', 'nox_program', 'side', 'default');
}
add_action('add_meta_boxes', 'nox_art_add_meta_boxes');

/* -------------------------------------------------------------------------
 * MIESTO – adresa + súradnice (s mini-mapou na klikacie zadanie polohy)
 * ---------------------------------------------------------------------- */
function nox_art_render_miesto_metabox($post) {
    wp_nonce_field('nox_art_save_miesto', 'nox_art_miesto_nonce');
    $adresa = get_post_meta($post->ID, '_nox_adresa', true);
    $lat = get_post_meta($post->ID, '_nox_lat', true);
    $lng = get_post_meta($post->ID, '_nox_lng', true);
    ?>
    <p>
        <label for="nox_adresa"><strong>Adresa</strong></label><br>
        <input type="text" id="nox_adresa" name="nox_adresa" class="widefat" value="<?php echo esc_attr($adresa); ?>" placeholder="napr. Nábrežná 12, Prievidza">
    </p>
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

    if (isset($_POST['nox_adresa'])) {
        update_post_meta($post_id, '_nox_adresa', sanitize_text_field($_POST['nox_adresa']));
    }
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
function nox_art_render_program_metabox($post) {
    wp_nonce_field('nox_art_save_program', 'nox_art_program_nonce');
    $datum = get_post_meta($post->ID, '_nox_datum', true);
    $cas_od = get_post_meta($post->ID, '_nox_cas_od', true);
    $cas_do = get_post_meta($post->ID, '_nox_cas_do', true);
    $miesto_id = get_post_meta($post->ID, '_nox_miesto_id', true);
    $miesta = get_posts(['post_type' => 'nox_miesto', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>
    <p>
        <label for="nox_datum"><strong>Dátum</strong></label><br>
        <input type="date" id="nox_datum" name="nox_datum" class="widefat" value="<?php echo esc_attr($datum); ?>">
    </p>
    <p style="display:flex;gap:12px">
        <label style="flex:1">Od<br><input type="time" id="nox_cas_od" name="nox_cas_od" class="widefat" value="<?php echo esc_attr($cas_od); ?>"></label>
        <label style="flex:1">Do (nepovinné)<br><input type="time" id="nox_cas_do" name="nox_cas_do" class="widefat" value="<?php echo esc_attr($cas_do); ?>"></label>
    </p>
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

    update_post_meta($post_id, '_nox_datum', isset($_POST['nox_datum']) ? sanitize_text_field($_POST['nox_datum']) : '');
    update_post_meta($post_id, '_nox_cas_od', isset($_POST['nox_cas_od']) ? sanitize_text_field($_POST['nox_cas_od']) : '');
    update_post_meta($post_id, '_nox_cas_do', isset($_POST['nox_cas_do']) ? sanitize_text_field($_POST['nox_cas_do']) : '');
    update_post_meta($post_id, '_nox_miesto_id', isset($_POST['nox_miesto_id']) ? absint($_POST['nox_miesto_id']) : 0);
}
add_action('save_post_nox_program', 'nox_art_save_program');

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
