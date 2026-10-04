<?php
if (!defined('ABSPATH')) exit;

/**
 * Meranie návštevnosti (Google Analytics 4) so súhlasom návštevníka a
 * prihlasovanie na newsletter do Brevo.
 *
 * Nastavenia sú v NOX:ART → Nastavenia → "Meranie a newsletter". API kľúč
 * Brevo sa ukladá len v databáze webu (nie v kóde ani v repozitári).
 */
define('NOX_ART_TRACKING_OPTION', 'nox_art_tracking');

function nox_art_tracking() {
    $o = get_option(NOX_ART_TRACKING_OPTION, []);
    $o = is_array($o) ? $o : [];
    return $o + ['ga_id' => '', 'brevo_key' => '', 'brevo_list' => 0, 'brevo_template' => 0];
}

/** ID merania GA4 (G-XXXXXXXX) alebo prázdny reťazec. */
function nox_art_ga_id() {
    $id = strtoupper(trim(nox_art_tracking()['ga_id']));
    return preg_match('/^G-[A-Z0-9]{6,}$/', $id) ? $id : '';
}

function nox_art_newsletter_ready() {
    $t = nox_art_tracking();
    return $t['brevo_key'] !== '' && (int) $t['brevo_list'] > 0;
}

/**
 * Údaje pre skript: ID merania, adresa a stav newsletteru.
 */
function nox_art_tracking_localize() {
    if (!wp_script_is('nox-art-site-js', 'enqueued')) return;
    wp_localize_script('nox-art-site-js', 'NOX_SITE_TRACK', [
        'gaId' => nox_art_ga_id(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'newsletter' => nox_art_newsletter_ready(),
    ]);
}
add_action('wp_enqueue_scripts', 'nox_art_tracking_localize', 30);

/**
 * Lišta so súhlasom – len keď je zadané ID merania.
 */
function nox_art_consent_banner() {
    if (!nox_art_ga_id() || !nox_art_site_sections()) return;
    ?>
    <div class="consent" data-consent hidden role="dialog" aria-labelledby="consent-title">
      <p class="consent-title" id="consent-title">Cookies a meranie</p>
      <p class="consent-text">Anonymne meriame návštevnosť (Google Analytics), aby sme vedeli, čo na webe zaujíma. Robíme to len s tvojím súhlasom.</p>
      <div class="consent-actions">
        <button type="button" class="consent-btn" data-consent-deny>Odmietnuť</button>
        <button type="button" class="consent-btn consent-btn-main" data-consent-accept>Súhlasím</button>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'nox_art_consent_banner', 5);

/* ---------------------------------------------------------------------
   Newsletter → Brevo
   ------------------------------------------------------------------ */

function nox_art_newsletter_reply($ok, $message, $status = 200) {
    wp_send_json(['ok' => $ok, 'message' => $message], $status);
}

function nox_art_handle_newsletter() {
    // Pasca pre roboty: skryté pole musí zostať prázdne.
    if (!empty($_POST['web'])) nox_art_newsletter_reply(true, 'Ďakujeme!');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!is_email($email)) nox_art_newsletter_reply(false, 'Skontroluj, prosím, e-mailovú adresu.', 400);
    if (empty($_POST['suhlas'])) nox_art_newsletter_reply(false, 'Pre prihlásenie je potrebný súhlas so zasielaním noviniek.', 400);

    if (!nox_art_newsletter_ready()) {
        nox_art_newsletter_reply(false, 'Prihlásenie zatiaľ nie je dostupné. Skús to prosím neskôr.', 503);
    }

    // Najviac 5 pokusov za hodinu z jednej adresy.
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $kluc = 'nox_nl_' . md5($ip);
    $pokusy = (int) get_transient($kluc);
    if ($pokusy >= 5) nox_art_newsletter_reply(false, 'Príliš veľa pokusov. Skús to prosím neskôr.', 429);
    set_transient($kluc, $pokusy + 1, HOUR_IN_SECONDS);

    $t = nox_art_tracking();
    $list = (int) $t['brevo_list'];
    $sablona = (int) $t['brevo_template'];

    if ($sablona > 0) {
        // Dvojité potvrdenie: Brevo pošle potvrdzovací e-mail zo šablóny.
        $url = 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation';
        $telo = [
            'email' => $email,
            'includeListIds' => [$list],
            'templateId' => $sablona,
            'redirectionUrl' => home_url('/'),
        ];
    } else {
        $url = 'https://api.brevo.com/v3/contacts';
        $telo = ['email' => $email, 'listIds' => [$list], 'updateEnabled' => true];
    }

    $odpoved = wp_remote_post($url, [
        'timeout' => 12,
        'headers' => [
            'api-key' => $t['brevo_key'],
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ],
        'body' => wp_json_encode($telo),
    ]);

    if (is_wp_error($odpoved)) {
        error_log('NOX:ART newsletter: ' . $odpoved->get_error_message());
        nox_art_newsletter_reply(false, 'Prihlásenie sa nepodarilo. Skús to prosím neskôr.', 502);
    }

    $kod = (int) wp_remote_retrieve_response_code($odpoved);
    $data = json_decode(wp_remote_retrieve_body($odpoved), true);

    // Už prihlásený kontakt Brevo hlási ako duplicitu – pre človeka je to úspech.
    $duplicita = $kod === 400 && is_array($data) && (($data['code'] ?? '') === 'duplicate_parameter');

    if (($kod >= 200 && $kod < 300) || $duplicita) {
        nox_art_newsletter_reply(true, $sablona > 0
            ? 'Skoro hotovo! Poslali sme ti e-mail, potvrď v ňom prihlásenie.'
            : 'Ďakujeme, si prihlásený/á!');
    }

    error_log('NOX:ART newsletter: Brevo ' . $kod . ' ' . wp_remote_retrieve_body($odpoved));
    nox_art_newsletter_reply(false, 'Prihlásenie sa nepodarilo. Skús to prosím neskôr.', 502);
}
add_action('wp_ajax_nopriv_nox_art_newsletter', 'nox_art_handle_newsletter');
add_action('wp_ajax_nox_art_newsletter', 'nox_art_handle_newsletter');
