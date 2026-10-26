<?php
if (!defined('ABSPATH')) exit;

/**
 * Registrácia na body sprievodného programu (workshopy, prechádzky …).
 *
 * V administrácii pri bode programu sa zapne registrácia a nastaví kapacita
 * na jeden termín. Na stránke má bod v detaile tlačidlo "Registrovať sa",
 * ktoré otvorí formulár. Prihlášky sa ukladajú do vlastnej tabuľky, človek
 * dostane potvrdenie e-mailom, organizátor upozornenie. Podujatie je
 * zdarma, preto sa nič neplatí a prihláška je potvrdená hneď.
 */
define('NOX_ART_REG_DB_VERSION', '1');
define('NOX_ART_REG_EMAIL_OPTION', 'nox_art_reg_email');
define('NOX_ART_REG_FROM_OPTION', 'nox_art_reg_from_name');

function nox_art_reg_table() {
    global $wpdb;
    return $wpdb->prefix . 'nox_registracie';
}

/** Vytvorí tabuľku, ak ešte nie je (alebo sa zmenila jej verzia). */
function nox_art_reg_maybe_install() {
    if (get_option('nox_art_reg_db') === NOX_ART_REG_DB_VERSION) return;
    global $wpdb;
    $tabulka = nox_art_reg_table();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$tabulka} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        termin varchar(40) NOT NULL DEFAULT '',
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(190) NOT NULL,
        phone varchar(50) NOT NULL DEFAULT '',
        num_people int(11) NOT NULL DEFAULT 1,
        status varchar(20) NOT NULL DEFAULT 'confirmed',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY post_termin (post_id, termin),
        KEY email (email)
    ) {$charset};");
    update_option('nox_art_reg_db', NOX_ART_REG_DB_VERSION, false);
}
add_action('init', 'nox_art_reg_maybe_install', 5);

/* ---------------------------------------------------------------------
   Nastavenie pri bode programu
   ------------------------------------------------------------------ */

function nox_art_reg_add_meta_box() {
    add_meta_box('nox_registracia', 'Registrácia', 'nox_art_reg_render_meta_box', 'nox_program', 'side', 'default');
}
add_action('add_meta_boxes', 'nox_art_reg_add_meta_box');

function nox_art_reg_render_meta_box($post) {
    wp_nonce_field('nox_art_reg_save', 'nox_art_reg_nonce');
    $zapnute = (bool) get_post_meta($post->ID, '_nox_reg_zapnute', true);
    $kapacita = (int) get_post_meta($post->ID, '_nox_reg_kapacita', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="nox_reg_zapnute" value="1" <?php checked($zapnute); ?>>
            <strong>Zapnúť registráciu</strong>
        </label>
    </p>
    <p>
        <label for="nox_reg_kapacita">Kapacita na jeden termín (osoby)</label><br>
        <input type="number" min="0" id="nox_reg_kapacita" name="nox_reg_kapacita" class="widefat" value="<?php echo $kapacita ?: ''; ?>" placeholder="bez limitu">
    </p>
    <p class="description">Každý termín (deň a čas) bodu programu má vlastnú kapacitu. Po jej naplnení sa termín označí ako obsadený. Registrácia sa uzavrie, keď termín začne. Prihlášky nájdeš v NOX:ART → Registrácie.</p>
    <?php
}

function nox_art_reg_save_meta($post_id) {
    if (!isset($_POST['nox_art_reg_nonce']) || !wp_verify_nonce($_POST['nox_art_reg_nonce'], 'nox_art_reg_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (!empty($_POST['nox_reg_zapnute'])) update_post_meta($post_id, '_nox_reg_zapnute', 1);
    else delete_post_meta($post_id, '_nox_reg_zapnute');

    $kapacita = isset($_POST['nox_reg_kapacita']) ? absint($_POST['nox_reg_kapacita']) : 0;
    if ($kapacita > 0) update_post_meta($post_id, '_nox_reg_kapacita', $kapacita);
    else delete_post_meta($post_id, '_nox_reg_kapacita');
}
add_action('save_post_nox_program', 'nox_art_reg_save_meta');

/* ---------------------------------------------------------------------
   Údaje o termínoch a voľných miestach
   ------------------------------------------------------------------ */

function nox_art_reg_enabled($post_id) {
    return (bool) get_post_meta($post_id, '_nox_reg_zapnute', true);
}

/** Termíny bodu programu: kľúč (dátum + čas od), popis, či už začal. */
function nox_art_reg_terminy($post_id) {
    $out = [];
    foreach (nox_art_get_terminy($post_id) as $t) {
        $datum = $t['datum'] ?? '';
        $od = $t['od'] ?? '';
        $kluc = trim($datum . ' ' . $od);
        $zacina = $datum ? strtotime($datum . ' ' . ($od ?: '00:00')) : 0;
        $out[] = [
            'kluc' => $kluc,
            'label' => nox_art_site_time_label($datum, $od, $t['do'] ?? '') ?: 'Termín',
            'uzavrete' => $zacina && $zacina < current_time('timestamp'),
        ];
    }
    return $out ?: [['kluc' => '', 'label' => 'Termín', 'uzavrete' => false]];
}

/** Obsadené miesta podľa termínu: kluc => počet osôb. */
function nox_art_reg_obsadene($post_id) {
    global $wpdb;
    $riadky = $wpdb->get_results($wpdb->prepare(
        'SELECT termin, SUM(num_people) AS osoby FROM ' . nox_art_reg_table() . " WHERE post_id = %d AND status = 'confirmed' GROUP BY termin",
        $post_id
    ));
    $mapa = [];
    foreach ($riadky as $r) $mapa[$r->termin] = (int) $r->osoby;
    return $mapa;
}

function nox_art_reg_info($post_id) {
    $kapacita = (int) get_post_meta($post_id, '_nox_reg_kapacita', true);
    $obsadene = nox_art_reg_obsadene($post_id);
    $terminy = [];
    foreach (nox_art_reg_terminy($post_id) as $t) {
        $zabrate = $obsadene[$t['kluc']] ?? 0;
        $t['volne'] = $kapacita > 0 ? max(0, $kapacita - $zabrate) : null;
        $t['zabrate'] = $zabrate;
        $terminy[] = $t;
    }
    return ['kapacita' => $kapacita, 'terminy' => $terminy];
}

function nox_art_reg_ajax_info() {
    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'nox_program' || get_post_status($post_id) !== 'publish' || !nox_art_reg_enabled($post_id)) {
        wp_send_json(['ok' => false], 404);
    }
    wp_send_json(['ok' => true] + nox_art_reg_info($post_id));
}
add_action('wp_ajax_nopriv_nox_art_reg_info', 'nox_art_reg_ajax_info');
add_action('wp_ajax_nox_art_reg_info', 'nox_art_reg_ajax_info');

/* ---------------------------------------------------------------------
   Odoslanie prihlášky
   ------------------------------------------------------------------ */

function nox_art_reg_reply($ok, $message, $status = 200) {
    wp_send_json(['ok' => $ok, 'message' => $message], $status);
}

function nox_art_reg_ajax_submit() {
    // Pasca pre roboty: skryté pole musí zostať prázdne.
    if (!empty($_POST['web'])) nox_art_reg_reply(true, 'Ďakujeme!');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $termin = isset($_POST['termin']) ? sanitize_text_field(wp_unslash($_POST['termin'])) : '';
    $meno = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $priezvisko = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $telefon = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $osoby = isset($_POST['num_people']) ? absint($_POST['num_people']) : 1;

    if (!$post_id || get_post_type($post_id) !== 'nox_program' || get_post_status($post_id) !== 'publish' || !nox_art_reg_enabled($post_id)) {
        nox_art_reg_reply(false, 'Na toto podujatie sa nedá registrovať.', 404);
    }
    if ($meno === '' || $priezvisko === '') nox_art_reg_reply(false, 'Vyplň meno a priezvisko.', 400);
    if (!is_email($email)) nox_art_reg_reply(false, 'Skontroluj, prosím, e-mailovú adresu.', 400);
    if (strlen(preg_replace('/\D/', '', $telefon)) < 9) nox_art_reg_reply(false, 'Skontroluj, prosím, telefónne číslo.', 400);
    if ($osoby < 1 || $osoby > 10) nox_art_reg_reply(false, 'Počet osôb musí byť od 1 do 10.', 400);
    if (empty($_POST['suhlas'])) nox_art_reg_reply(false, 'Pre registráciu je potrebný súhlas so spracovaním údajov.', 400);

    // Najviac 8 pokusov za hodinu z jednej adresy.
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $kluc_limitu = 'nox_reg_' . md5($ip);
    $pokusy = (int) get_transient($kluc_limitu);
    if ($pokusy >= 8) nox_art_reg_reply(false, 'Príliš veľa pokusov. Skús to prosím neskôr.', 429);
    set_transient($kluc_limitu, $pokusy + 1, HOUR_IN_SECONDS);

    global $wpdb;
    // Zámok na podujatie, aby dve naraz odoslané prihlášky neprekročili kapacitu.
    $zamok = 'nox_art_reg_' . $post_id;
    $wpdb->query($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $zamok));

    $info = nox_art_reg_info($post_id);
    $vybrany = null;
    foreach ($info['terminy'] as $t) {
        if ($t['kluc'] === $termin) { $vybrany = $t; break; }
    }

    $chyba = '';
    if (!$vybrany) $chyba = 'Vyber termín.';
    elseif ($vybrany['uzavrete']) $chyba = 'Registrácia na tento termín je už uzavretá.';
    elseif ($vybrany['volne'] !== null && $osoby > $vybrany['volne']) {
        $chyba = $vybrany['volne'] > 0
            ? 'Na tento termín zostáva už len ' . $vybrany['volne'] . ' voľných miest.'
            : 'Tento termín je už obsadený.';
    } else {
        $existuje = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . nox_art_reg_table() . " WHERE post_id = %d AND termin = %s AND email = %s AND status = 'confirmed'",
            $post_id, $termin, $email
        ));
        if ($existuje) $chyba = 'Na tento termín si už prihlásený/á s týmto e-mailom.';
    }

    if ($chyba !== '') {
        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $zamok));
        nox_art_reg_reply(false, $chyba, 409);
    }

    $vlozene = $wpdb->insert(nox_art_reg_table(), [
        'post_id' => $post_id,
        'termin' => $termin,
        'first_name' => $meno,
        'last_name' => $priezvisko,
        'email' => $email,
        'phone' => $telefon,
        'num_people' => $osoby,
        'status' => 'confirmed',
        'created_at' => current_time('mysql'),
    ]);
    $id = (int) $wpdb->insert_id;
    $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $zamok));

    if (!$vlozene) {
        error_log('NOX:ART registrácia: nepodarilo sa uložiť – ' . $wpdb->last_error);
        nox_art_reg_reply(false, 'Registráciu sa nepodarilo uložiť. Skús to prosím znova.', 500);
    }

    $prihlaska = (object) [
        'id' => $id, 'post_id' => $post_id, 'termin' => $termin, 'first_name' => $meno,
        'last_name' => $priezvisko, 'email' => $email, 'phone' => $telefon, 'num_people' => $osoby,
    ];
    nox_art_reg_send_confirmation($prihlaska, $vybrany['label']);
    nox_art_reg_send_admin_notice($prihlaska, $vybrany['label']);

    nox_art_reg_reply(true, 'Registrácia prijatá! Potvrdenie sme poslali na ' . $email . '.');
}
add_action('wp_ajax_nopriv_nox_art_reg_submit', 'nox_art_reg_ajax_submit');
add_action('wp_ajax_nox_art_reg_submit', 'nox_art_reg_ajax_submit');

/* ---------------------------------------------------------------------
   E-maily
   ------------------------------------------------------------------ */

function nox_art_reg_admin_email() {
    $email = get_option(NOX_ART_REG_EMAIL_OPTION, '');
    return is_email($email) ? $email : get_option('admin_email');
}

function nox_art_reg_headers() {
    $meno = get_option(NOX_ART_REG_FROM_OPTION, '') ?: 'NOX:ART';
    return [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $meno . ' <' . nox_art_reg_admin_email() . '>',
    ];
}

function nox_art_reg_wrap($obsah) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:0;background:#efeedc;font-family:Helvetica,Arial,sans-serif;color:#2d2d2d">'
        . '<div style="max-width:560px;margin:28px auto;background:#f7f5e8;border-radius:24px;overflow:hidden">'
        . '<div style="background:#2d2d2d;color:#f7f5e8;padding:26px 32px"><div style="font-size:26px;font-weight:700;letter-spacing:-1px">NOX:ART</div>'
        . '<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;opacity:.7;margin-top:4px">Sídlisko Píly · 30.–31. 10. 2026</div></div>'
        . '<div style="padding:30px 32px;line-height:1.7;font-size:15px">' . $obsah . '</div>'
        . '<div style="padding:16px 32px;background:#efeedc;font-size:12px;color:#6b6b63;text-align:center">© ' . esc_html(date('Y')) . ' Ars Preuge</div>'
        . '</div></body></html>';
}

function nox_art_reg_box($riadky) {
    $html = '<div style="background:#fff;border-left:4px solid #2d2d2d;border-radius:8px;padding:14px 18px;margin:18px 0">';
    foreach ($riadky as $nazov => $hodnota) {
        $html .= '<p style="margin:4px 0"><strong>' . esc_html($nazov) . ':</strong> ' . esc_html($hodnota) . '</p>';
    }
    return $html . '</div>';
}

function nox_art_reg_send_confirmation($p, $termin_label) {
    $nazov = get_the_title($p->post_id);
    $osoby = (int) $p->num_people;
    $obsah = '<p>Ahoj <strong>' . esc_html($p->first_name) . '</strong>,</p>'
        . '<p>ďakujeme za registráciu. Tvoje miesto je potvrdené.</p>'
        . nox_art_reg_box([
            'Podujatie' => $nazov,
            'Termín' => $termin_label,
            'Počet osôb' => (string) $osoby,
        ])
        . '<p>Vstup je zdarma. Ak sa nemôžeš zúčastniť, daj nám prosím vedieť na '
        . '<a href="mailto:' . esc_attr(antispambot(nox_art_reg_admin_email())) . '">' . esc_html(antispambot(nox_art_reg_admin_email())) . '</a>, uvoľníme miesto pre ďalších.</p>'
        . '<p>Tešíme sa na teba!</p>';

    wp_mail($p->email, 'Potvrdenie registrácie — ' . $nazov, nox_art_reg_wrap($obsah), nox_art_reg_headers());
}

function nox_art_reg_send_admin_notice($p, $termin_label) {
    $nazov = get_the_title($p->post_id);
    $obsah = '<p>Nová registrácia na festivalovom webe.</p>'
        . nox_art_reg_box([
            'Podujatie' => $nazov,
            'Termín' => $termin_label,
            'Meno' => $p->first_name . ' ' . $p->last_name,
            'E-mail' => $p->email,
            'Telefón' => $p->phone,
            'Počet osôb' => (string) (int) $p->num_people,
        ])
        . '<p><a href="' . esc_url(admin_url('admin.php?page=nox-art-registracie')) . '">Otvoriť zoznam registrácií</a></p>';

    wp_mail(nox_art_reg_admin_email(), 'Nová registrácia — ' . $nazov, nox_art_reg_wrap($obsah), nox_art_reg_headers());
}

function nox_art_reg_send_cancelled($p, $termin_label) {
    $nazov = get_the_title($p->post_id);
    $obsah = '<p>Ahoj <strong>' . esc_html($p->first_name) . '</strong>,</p>'
        . '<p>tvoja registrácia bola zrušená.</p>'
        . nox_art_reg_box(['Podujatie' => $nazov, 'Termín' => $termin_label])
        . '<p>Ak ide o omyl, napíš nám na <a href="mailto:' . esc_attr(antispambot(nox_art_reg_admin_email())) . '">' . esc_html(antispambot(nox_art_reg_admin_email())) . '</a>.</p>';

    wp_mail($p->email, 'Zrušenie registrácie — ' . $nazov, nox_art_reg_wrap($obsah), nox_art_reg_headers());
}

/* ---------------------------------------------------------------------
   Administrácia: zoznam registrácií
   ------------------------------------------------------------------ */

function nox_art_reg_admin_menu() {
    add_submenu_page('nox-art-festival', 'Registrácie', 'Registrácie', 'manage_options', 'nox-art-registracie', 'nox_art_reg_render_page');
}
add_action('admin_menu', 'nox_art_reg_admin_menu', 25);

function nox_art_reg_label_termin($post_id, $kluc) {
    foreach (nox_art_reg_terminy($post_id) as $t) {
        if ($t['kluc'] === $kluc) return $t['label'];
    }
    return $kluc ?: 'Termín';
}

function nox_art_reg_render_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $tabulka = nox_art_reg_table();

    $posts = get_posts([
        'post_type' => 'nox_program',
        'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_key' => '_nox_reg_zapnute',
        'meta_value' => '1',
    ]);
    $oznam = isset($_GET['nox_reg_notice']) ? sanitize_key($_GET['nox_reg_notice']) : '';
    ?>
    <div class="wrap">
        <h1>Registrácie</h1>
        <?php if ($oznam === 'cancelled'): ?>
        <div class="notice notice-success is-dismissible"><p>Registrácia bola zrušená a človeku sme poslali e-mail.</p></div>
        <?php endif; ?>

        <?php if (!$posts): ?>
        <p>Zatiaľ nemá registráciu zapnutú žiadny bod programu. Zapni ju pri úprave bodu programu v bloku „Registrácia“.</p>
        <?php endif; ?>

        <?php foreach ($posts as $post):
            $info = nox_art_reg_info($post->ID);
            $riadky = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tabulka} WHERE post_id = %d ORDER BY termin ASC, created_at ASC", $post->ID));
            $csv = wp_nonce_url(admin_url('admin-post.php?action=nox_art_reg_csv&post_id=' . $post->ID), 'nox_art_reg_csv_' . $post->ID);
        ?>
        <h2 style="margin-top:32px"><?php echo esc_html(get_the_title($post)); ?>
            <a class="button" style="margin-left:12px" href="<?php echo esc_url($csv); ?>">Stiahnuť CSV</a>
            <a class="button button-link" href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">Upraviť podujatie</a>
        </h2>
        <p>
            <?php foreach ($info['terminy'] as $t): ?>
            <strong><?php echo esc_html($t['label']); ?>:</strong>
            <?php echo (int) $t['zabrate']; ?><?php echo $info['kapacita'] ? ' / ' . (int) $info['kapacita'] : ''; ?> osôb
            &nbsp;&nbsp;
            <?php endforeach; ?>
        </p>

        <table class="widefat striped" style="max-width:1100px">
            <thead><tr><th>Termín</th><th>Meno</th><th>E-mail</th><th>Telefón</th><th>Osoby</th><th>Prihlásený</th><th>Stav</th><th></th></tr></thead>
            <tbody>
            <?php if (!$riadky): ?>
                <tr><td colspan="8">Zatiaľ žiadne registrácie.</td></tr>
            <?php endif; ?>
            <?php foreach ($riadky as $r):
                $zrusit = wp_nonce_url(admin_url('admin-post.php?action=nox_art_reg_cancel&id=' . (int) $r->id), 'nox_art_reg_cancel_' . (int) $r->id);
            ?>
                <tr<?php echo $r->status === 'cancelled' ? ' style="opacity:.55"' : ''; ?>>
                    <td><?php echo esc_html(nox_art_reg_label_termin($post->ID, $r->termin)); ?></td>
                    <td><?php echo esc_html($r->first_name . ' ' . $r->last_name); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td>
                    <td><?php echo esc_html($r->phone); ?></td>
                    <td><?php echo (int) $r->num_people; ?></td>
                    <td><?php echo esc_html(mysql2date('j. n. Y H:i', $r->created_at)); ?></td>
                    <td><?php echo $r->status === 'cancelled' ? 'zrušená' : 'potvrdená'; ?></td>
                    <td><?php if ($r->status !== 'cancelled'): ?><a href="<?php echo esc_url($zrusit); ?>" onclick="return confirm('Zrušiť túto registráciu a poslať e-mail?');">Zrušiť</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
    </div>
    <?php
}

function nox_art_reg_handle_cancel() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    check_admin_referer('nox_art_reg_cancel_' . $id);

    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . nox_art_reg_table() . ' WHERE id = %d', $id));
    if ($r && $r->status !== 'cancelled') {
        $wpdb->update(nox_art_reg_table(), ['status' => 'cancelled'], ['id' => $id]);
        nox_art_reg_send_cancelled($r, nox_art_reg_label_termin($r->post_id, $r->termin));
    }
    wp_safe_redirect(add_query_arg(['page' => 'nox-art-registracie', 'nox_reg_notice' => 'cancelled'], admin_url('admin.php')));
    exit;
}
add_action('admin_post_nox_art_reg_cancel', 'nox_art_reg_handle_cancel');

function nox_art_reg_handle_csv() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
    check_admin_referer('nox_art_reg_csv_' . $post_id);

    global $wpdb;
    $riadky = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . nox_art_reg_table() . ' WHERE post_id = %d ORDER BY termin ASC, created_at ASC', $post_id));

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="registracie-' . $post_id . '.csv"');
    echo "\xEF\xBB\xBF";   // BOM, aby Excel čítal diakritiku
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Termín', 'Meno', 'Priezvisko', 'E-mail', 'Telefón', 'Osoby', 'Stav', 'Prihlásený'], ';');
    foreach ($riadky as $r) {
        // Bunky začínajúce =, +, -, @ by Excel zobral ako vzorec.
        $bezpecne = function ($v) { return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; };
        fputcsv($out, [
            nox_art_reg_label_termin($post_id, $r->termin),
            $bezpecne($r->first_name),
            $bezpecne($r->last_name),
            $bezpecne($r->email),
            $bezpecne($r->phone),
            $r->num_people,
            $r->status === 'cancelled' ? 'zrušená' : 'potvrdená',
            $r->created_at,
        ], ';');
    }
    fclose($out);
    exit;
}
add_action('admin_post_nox_art_reg_csv', 'nox_art_reg_handle_csv');
