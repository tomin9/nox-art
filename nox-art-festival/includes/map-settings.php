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
    add_submenu_page('nox-art-festival', 'Nastavenia', 'Nastavenia', 'manage_options', 'nox-art-map-settings', 'nox_art_render_map_settings_page');
}
add_action('admin_menu', 'nox_art_map_settings_menu', 20);

define('NOX_ART_SOCIAL_OPTION', 'nox_art_social_links');

/**
 * Odkazy na sociálne siete – používa ich hlavička aj päta. Prázdny odkaz
 * znamená, že sa ikona nezobrazí.
 */
function nox_art_social_networks() {
    return [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
    ];
}

function nox_art_get_social_links() {
    $ulozene = get_option(NOX_ART_SOCIAL_OPTION, []);
    $odkazy = [];
    foreach (nox_art_social_networks() as $kluc => $label) {
        $odkazy[$kluc] = is_array($ulozene) && !empty($ulozene[$kluc]) ? $ulozene[$kluc] : '';
    }
    return apply_filters('nox_art_social_links', $odkazy);
}

/**
 * Ikony sietí – jednoduché jednofarebné SVG, ktoré preberá farbu textu.
 */
function nox_art_social_icon($siet) {
    $ikony = [
        'facebook' => '<path d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.5-1.5h1.6V3.9c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.1V10H7.5v3h2.7v8h3.3Z"/>',
        'instagram' => '<path d="M12 7.4a4.6 4.6 0 1 0 0 9.2 4.6 4.6 0 0 0 0-9.2Zm0 7.6a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm5.9-7.8a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM12 3.6c2.3 0 2.6 0 3.5.1.9 0 1.5.2 2 .4.6.2 1 .5 1.5 1 .5.4.8.9 1 1.4.2.5.4 1.2.4 2 0 .9.1 1.2.1 3.5s0 2.6-.1 3.5c0 .9-.2 1.5-.4 2-.2.6-.5 1-1 1.5-.4.5-.9.8-1.4 1-.5.2-1.2.4-2 .4-.9 0-1.2.1-3.5.1s-2.6 0-3.5-.1c-.9 0-1.5-.2-2-.4-.6-.2-1-.5-1.5-1-.5-.4-.8-.9-1-1.4-.2-.5-.4-1.2-.4-2 0-.9-.1-1.2-.1-3.5s0-2.6.1-3.5c0-.9.2-1.5.4-2 .2-.6.5-1 1-1.5.4-.5.9-.8 1.4-1 .5-.2 1.2-.4 2-.4.9 0 1.2-.1 3.5-.1Zm0 1.8c-2.2 0-2.5 0-3.4.1-.8 0-1.2.2-1.5.3-.4.1-.7.3-1 .6-.3.3-.5.6-.6 1-.1.3-.3.7-.3 1.5 0 .9-.1 1.2-.1 3.4s0 2.5.1 3.4c0 .8.2 1.2.3 1.5.1.4.3.7.6 1 .3.3.6.5 1 .6.3.1.7.3 1.5.3.9 0 1.2.1 3.4.1s2.5 0 3.4-.1c.8 0 1.2-.2 1.5-.3.4-.1.7-.3 1-.6.3-.3.5-.6.6-1 .1-.3.3-.7.3-1.5 0-.9.1-1.2.1-3.4s0-2.5-.1-3.4c0-.8-.2-1.2-.3-1.5a2.6 2.6 0 0 0-.6-1 2.6 2.6 0 0 0-1-.6c-.3-.1-.7-.3-1.5-.3-.9 0-1.2-.1-3.4-.1Z"/>',
        'youtube' => '<path d="M21.6 8.1c-.2-.9-.8-1.6-1.6-1.8C18.5 5.9 12 5.9 12 5.9s-6.5 0-8 .4c-.8.2-1.4.9-1.6 1.8C2 9.6 2 12 2 12s0 2.4.4 3.9c.2.9.8 1.6 1.6 1.8 1.5.4 8 .4 8 .4s6.5 0 8-.4c.8-.2 1.4-.9 1.6-1.8.4-1.5.4-3.9.4-3.9s0-2.4-.4-3.9ZM10 15V9l5.2 3L10 15Z"/>',
    ];
    if (empty($ikony[$siet])) return '';
    return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false">' . $ikony[$siet] . '</svg>';
}

define('NOX_ART_SUPPORT_OPTION', 'nox_art_support_notes');

/**
 * Doložky o finančnej podpore pod logami partnerov. Dajú sa prepísať
 * v nastaveniach (jedna na riadok); prázdne nastavenie znamená predvolený
 * zoznam nižšie.
 */
function nox_art_default_support_notes() {
    return [
        'S finančnou podporou Ministerstva cestovného ruchu a športu SR',
        'Tento projekt je realizovaný vďaka podpore Európskeho hlavného mesta kultúry Trenčín 2026 a Trenčianskeho samosprávneho kraja',
        'Podujatie z verejných zdrojov podporil Fond na podporu umenia',
        'Projekt sa realizuje vďaka podpore Nadácie Slovenskej sporiteľne a Nadácie otvorenej spoločnosti v rámci Fondu pre regionálnu kultúru',
        'Realizované s finančnou podporou Trenčianskeho samosprávneho kraja',
        'Podujatie realizované s finančnou podporou mesta Prievidza',
    ];
}

function nox_art_support_notes() {
    $ulozene = get_option(NOX_ART_SUPPORT_OPTION, '');
    if (is_string($ulozene) && trim($ulozene) !== '') {
        $riadky = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $ulozene)));
    } else {
        $riadky = nox_art_default_support_notes();
    }
    return apply_filters('nox_art_support_notes', array_values($riadky));
}

function nox_art_handle_save_map_settings() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('nox_art_save_map_settings');

    update_option(NOX_ART_MAP_OPTION, [
        'token' => isset($_POST['nox_art_mapbox_token']) ? sanitize_text_field($_POST['nox_art_mapbox_token']) : '',
        'style' => isset($_POST['nox_art_mapbox_style']) && $_POST['nox_art_mapbox_style'] !== ''
            ? sanitize_text_field($_POST['nox_art_mapbox_style'])
            : 'mapbox://styles/mapbox/dark-v11',
    ]);

    $socialne = [];
    foreach (nox_art_social_networks() as $kluc => $label) {
        $hodnota = isset($_POST['nox_art_social_' . $kluc]) ? esc_url_raw($_POST['nox_art_social_' . $kluc]) : '';
        if ($hodnota) $socialne[$kluc] = $hodnota;
    }
    update_option(NOX_ART_SOCIAL_OPTION, $socialne);

    $dolozky = isset($_POST['nox_art_support_notes']) ? sanitize_textarea_field($_POST['nox_art_support_notes']) : '';
    update_option(NOX_ART_SUPPORT_OPTION, $dolozky);

    update_option(NOX_ART_PAGE_OPTION, [
        'page' => isset($_POST['nox_art_festival_page']) ? absint($_POST['nox_art_festival_page']) : 0,
        'template' => isset($_POST['nox_art_festival_template']) && isset(nox_art_site_template_map()[$_POST['nox_art_festival_template']])
            ? sanitize_text_field($_POST['nox_art_festival_template'])
            : 'nox-art-site-template.php',
    ]);
    // Adresy pohľadov sa odvíjajú od tejto stránky – pravidlá treba prepísať.
    delete_option('nox_art_routes_hash');

    // Ukladá sa len ID merania (prípadný starý kľúč Brevo sa tým z databázy odstráni).
    update_option(NOX_ART_TRACKING_OPTION, [
        'ga_id' => isset($_POST['nox_art_ga_id']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['nox_art_ga_id']))) : '',
    ], false);

    $domena = isset($_POST['nox_art_festival_domain']) ? sanitize_text_field($_POST['nox_art_festival_domain']) : '';
    update_option(NOX_ART_DOMAIN_OPTION, $domena);
    update_option('nox_art_festival_domain_redirect', empty($_POST['nox_art_festival_domain_redirect']) ? '' : '1');

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
        <h1>Nastavenia NOX:ART</h1>
        <h2>Mapa</h2>
        <?php if ($notice === 'saved'): ?><div class="notice notice-success is-dismissible"><p>Uložené.</p></div><?php endif; ?>
        <?php if ($notice === 'merged'): ?>
        <div class="notice notice-success is-dismissible"><p>Zlúčených duplicitných kategórií: <?php echo (int) ($_GET['nox_art_count'] ?? 0); ?>.</p></div>
        <?php endif; ?>
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
            <h2>Sociálne siete</h2>
            <p>Odkazy sa zobrazia ako ikony v hlavičke aj v päte. Prázdne pole znamená, že sa ikona nezobrazí.</p>
            <table class="form-table" role="presentation">
                <?php $socialne = nox_art_get_social_links(); ?>
                <?php foreach (nox_art_social_networks() as $kluc => $label): ?>
                <tr>
                    <th scope="row"><label for="nox_art_social_<?php echo esc_attr($kluc); ?>"><?php echo esc_html($label); ?></label></th>
                    <td><input type="url" id="nox_art_social_<?php echo esc_attr($kluc); ?>" name="nox_art_social_<?php echo esc_attr($kluc); ?>" class="regular-text" style="width:480px" value="<?php echo esc_attr($socialne[$kluc]); ?>" placeholder="https://"></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <h2>Festivalová stránka</h2>
            <p>Ktorá stránka webu má vykresľovať festival. Pri blokových témach (napr. Twenty Twenty-Five)
            sa šablóny pluginu v editore stránky neponúkajú, preto sa dá priradiť tu.</p>
            <table class="form-table" role="presentation">
                <?php $priradene = nox_art_site_assigned_page(); ?>
                <tr>
                    <th scope="row"><label for="nox_art_festival_page">Stránka</label></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'id' => 'nox_art_festival_page',
                            'name' => 'nox_art_festival_page',
                            'selected' => $priradene['page'],
                            'show_option_none' => '— žiadna (použije sa šablóna zo stránky) —',
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nox_art_festival_template">Sekcie</label></th>
                    <td>
                        <select id="nox_art_festival_template" name="nox_art_festival_template" style="min-width:380px">
                            <?php foreach (nox_art_site_template_map() as $subor => $def): ?>
                            <option value="<?php echo esc_attr($subor); ?>" <?php selected($priradene['template'], $subor); ?>><?php echo esc_html($def['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Pre samostatný festivalový web vyber „Celá stránka (všetky sekcie)“.</p>
                    </td>
                </tr>
            </table>

            <h2>Vlastná doména festivalu</h2>
            <p>Keď je doména nasmerovaná na tento hosting, festival sa na nej zobrazí rovno na úvodnej strane
            (napr. <code>noxart.sk/diela/</code>). Pôvodná adresa stránky sa potom natrvalo presmeruje sem.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="nox_art_festival_domain">Doména</label></th>
                    <td>
                        <input type="text" id="nox_art_festival_domain" name="nox_art_festival_domain" class="regular-text" style="width:480px" value="<?php echo esc_attr(get_option(NOX_ART_DOMAIN_OPTION, '')); ?>" placeholder="noxart.sk">
                        <p class="description">Bez <code>https://</code> aj bez lomky. Prázdne pole = festival ostáva len na pôvodnej adrese.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Presmerovanie</th>
                    <td>
                        <label>
                            <input type="checkbox" name="nox_art_festival_domain_redirect" value="1" <?php checked(get_option('nox_art_festival_domain_redirect', '1'), '1'); ?>>
                            Pôvodnú adresu festivalovej stránky natrvalo (301) presmerovať na novú doménu
                        </label>
                    </td>
                </tr>
            </table>

            <h2>Doložky o podpore</h2>
            <p>Vypíšu sa pod logami partnerov, každý riadok ako samostatná veta. Prázdne pole znamená predvolený zoznam.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="nox_art_support_notes">Texty (jeden na riadok)</label></th>
                    <td>
                        <textarea id="nox_art_support_notes" name="nox_art_support_notes" class="large-text code" rows="8" style="width:640px"><?php echo esc_textarea(get_option(NOX_ART_SUPPORT_OPTION, '') ?: implode("\n", nox_art_default_support_notes())); ?></textarea>
                    </td>
                </tr>
            </table>
            <h2>Meranie</h2>
            <?php $mer = nox_art_tracking(); ?>
            <p>Google Analytics sa zapne až po súhlase návštevníka (zobrazí sa lišta).</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="nox_art_ga_id">ID merania Google Analytics</label></th>
                    <td>
                        <input type="text" id="nox_art_ga_id" name="nox_art_ga_id" class="regular-text" value="<?php echo esc_attr($mer['ga_id']); ?>" placeholder="G-XXXXXXXXXX">
                        <p class="description">Nájdeš ho v Analytics: Správca → Dátové toky → webový tok. Prázdne = meranie aj lišta sú vypnuté.</p>
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
        <h2>Údržba</h2>
        <?php $duplikaty = nox_art_duplicate_category_groups(); ?>
        <?php if ($duplikaty): ?>
        <p>Našli sa kategórie s rovnakým názvom (typicky po prenose obsahu z iného webu):
            <strong><?php echo esc_html(implode(', ', array_map(function ($skupina) { return $skupina[0]->name; }, $duplikaty))); ?></strong>.
            Zlúčením sa položky presunú pod pôvodnú kategóriu a duplicity sa zmažú.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('nox_art_merge_categories'); ?>
            <input type="hidden" name="action" value="nox_art_merge_categories">
            <?php submit_button('Zlúčiť duplicitné kategórie', 'secondary'); ?>
        </form>
        <?php else: ?>
        <p class="description">Duplicitné kategórie sa nenašli.</p>
        <?php endif; ?>

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
