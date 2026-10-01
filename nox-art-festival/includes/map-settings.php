<?php
if (!defined('ABSPATH')) exit;

define('NOX_ART_MAP_OPTION', 'nox_art_map_settings');

function nox_art_get_map_settings() {
    $defaults = ['token' => '', 'style' => 'mapbox://styles/mapbox/dark-v11'];
    $settings = get_option(NOX_ART_MAP_OPTION, $defaults);
    return wp_parse_args(is_array($settings) ? $settings : [], $defaults);
}

/**
 * Token/štýl sa dajú prepísať aj cez filter (napr. z wp-config.php alebo
 * iného pluginu) – nastavenie vo wp-adminovi je len pohodlný predvolený
 * spôsob, aby sa nikdy nemuselo nič ukladať priamo do kódu pluginu (a teda
 * ani do git repozitára).
 */
function nox_art_mapbox_token() {
    $settings = nox_art_get_map_settings();
    return apply_filters('nox_art_mapbox_token', $settings['token']);
}
function nox_art_mapbox_style() {
    $settings = nox_art_get_map_settings();
    return apply_filters('nox_art_mapbox_style', $settings['style']);
}

function nox_art_map_settings_menu() {
    add_submenu_page('nox-art-festival', 'Nastavenia mapy', 'Nastavenia mapy', 'manage_options', 'nox-art-map-settings', 'nox_art_render_map_settings_page');
}
add_action('admin_menu', 'nox_art_map_settings_menu', 20);

function nox_art_handle_save_map_settings() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('nox_art_save_map_settings');

    update_option(NOX_ART_MAP_OPTION, [
        'token' => isset($_POST['nox_art_mapbox_token']) ? sanitize_text_field($_POST['nox_art_mapbox_token']) : '',
        'style' => isset($_POST['nox_art_mapbox_style']) && $_POST['nox_art_mapbox_style'] !== ''
            ? sanitize_text_field($_POST['nox_art_mapbox_style'])
            : 'mapbox://styles/mapbox/dark-v11',
    ]);

    wp_safe_redirect(add_query_arg(['page' => 'nox-art-map-settings', 'nox_art_notice' => 'saved'], admin_url('admin.php')));
    exit;
}
add_action('admin_post_nox_art_save_map_settings', 'nox_art_handle_save_map_settings');

function nox_art_render_map_settings_page() {
    if (!current_user_can('manage_options')) return;
    $settings = nox_art_get_map_settings();
    $notice = isset($_GET['nox_art_notice']) ? sanitize_key($_GET['nox_art_notice']) : '';
    ?>
    <div class="wrap">
        <h1>Nastavenia mapy</h1>
        <?php if ($notice === 'saved'): ?><div class="notice notice-success is-dismissible"><p>Uložené.</p></div><?php endif; ?>
        <p>Mapa na podstránke festivalu (shortcode <code>[nox_art]</code>) beží na <a href="https://www.mapbox.com/" target="_blank" rel="noopener">Mapbox</a>. Bez access tokenu sa mapa nezobrazí.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('nox_art_save_map_settings'); ?>
            <input type="hidden" name="action" value="nox_art_save_map_settings">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="nox_art_mapbox_token">Mapbox access token</label></th>
                    <td>
                        <input type="text" id="nox_art_mapbox_token" name="nox_art_mapbox_token" class="regular-text" style="width:480px" value="<?php echo esc_attr($settings['token']); ?>" placeholder="pk.…">
                        <p class="description">Verejný ("public") token z <a href="https://account.mapbox.com/access-tokens/" target="_blank" rel="noopener">account.mapbox.com/access-tokens</a> – je bezpečné mať ho vo frontend kóde, no napriek tomu ho z bezpečnostných dôvodov neukladáme priamo do kódu pluginu, len sem do databázy.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nox_art_mapbox_style">Štýl mapy</label></th>
                    <td>
                        <input type="text" id="nox_art_mapbox_style" name="nox_art_mapbox_style" class="regular-text" style="width:480px" value="<?php echo esc_attr($settings['style']); ?>" placeholder="mapbox://styles/mapbox/dark-v11">
                        <p class="description">Napr. tvoj vlastný štýl z <a href="https://studio.mapbox.com/" target="_blank" rel="noopener">Mapbox Studio</a> (tvar <code>mapbox://styles/účet/id_štýlu</code>).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Uložiť nastavenia'); ?>
        </form>

        <?php
        /* Verzia a čas poslednej zmeny súborov – podľa toho sa dá bez prístupu
           k súborom zistiť, či sa posledná synchronizácia z GitHubu naozaj
           prejavila na tejto inštalácii. */
        $template = NOX_ART_DIR . 'templates/parts/program.php';
        $time = file_exists($template) ? filemtime($template) : 0;
        ?>
        <h2>Verzia pluginu</h2>
        <table class="widefat striped" style="max-width:640px">
            <tbody>
                <tr><td><strong>Verzia pluginu</strong></td><td><?php echo esc_html(NOX_ART_VERSION); ?></td></tr>
                <tr><td><strong>Posledná zmena súborov</strong></td><td><?php echo $time ? esc_html(date_i18n('j.n.Y H:i:s', $time)) : '—'; ?></td></tr>
                <tr><td><strong>Priečinok pluginu</strong></td><td><code><?php echo esc_html(NOX_ART_DIR); ?></code></td></tr>
            </tbody>
        </table>
        <p class="description">Ak po synchronizácii z GitHubu tieto údaje zostanú rovnaké, nové súbory sa na túto inštaláciu nedostali.</p>
    </div>
    <?php
}
