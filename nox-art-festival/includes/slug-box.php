<?php
if (!defined('ABSPATH')) exit;

/**
 * Adresa položky (napr. /diela/neruste-moje-kruhy/). WordPress ju vyrobí z
 * názvu pri prvom uložení a potom ju už nemení, takže po premenovaní diela
 * ostane v adrese starý názov. Záznamy nemajú vlastnú verejnú stránku, preto
 * WordPress pole na úpravu adresy nezobrazuje – dopĺňame ho sami.
 */
function nox_art_slug_post_types() {
    return ['nox_dielo', 'nox_program', 'nox_podnik', 'nox_umelec'];
}

function nox_art_slug_add_meta_box() {
    foreach (nox_art_slug_post_types() as $typ) {
        add_meta_box('nox_slug', 'Adresa na webe', 'nox_art_slug_render', $typ, 'side', 'default');
    }
}
add_action('add_meta_boxes', 'nox_art_slug_add_meta_box');

function nox_art_slug_render($post) {
    wp_nonce_field('nox_art_slug', 'nox_art_slug_nonce');
    $kam = [
        'nox_dielo' => 'diela',
        'nox_umelec' => 'autori',
    ][$post->post_type] ?? '…';
    ?>
    <p>
        <label for="nox_slug"><strong>Koncovka adresy</strong></label><br>
        <input type="text" id="nox_slug" name="nox_slug" class="widefat" value="<?php echo esc_attr($post->post_name); ?>" placeholder="vyplní sa z názvu">
    </p>
    <p class="description">
        Adresa vyzerá ako <code>/<?php echo esc_html($kam); ?>/<strong><?php echo esc_html($post->post_name ?: 'koncovka'); ?></strong>/</code>.
        Po zmene názvu ju prepíš, alebo nechaj pole <strong>prázdne</strong> – adresa sa vytvorí znova z aktuálneho názvu.
        Starý odkaz po zmene prestane fungovať.
    </p>
    <?php
}

function nox_art_slug_save($post_id, $post) {
    if (!isset($_POST['nox_art_slug_nonce']) || !wp_verify_nonce($_POST['nox_art_slug_nonce'], 'nox_art_slug')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) return;

    $slug = sanitize_title(wp_unslash($_POST['nox_slug'] ?? ''));
    // Prázdne pole = vytvoriť znova z názvu.
    if ($slug === '') $slug = sanitize_title($post->post_title);
    if ($slug === '' || $slug === $post->post_name) return;

    $slug = wp_unique_post_slug($slug, $post_id, $post->post_status, $post->post_type, $post->post_parent);

    // wp_update_post znova spustí save_post – odpojíme sa, aby sa to nezacyklilo.
    remove_action('save_post', 'nox_art_slug_save', 20);
    wp_update_post(['ID' => $post_id, 'post_name' => $slug]);
    add_action('save_post', 'nox_art_slug_save', 20, 2);
}
add_action('save_post', 'nox_art_slug_save', 20, 2);
