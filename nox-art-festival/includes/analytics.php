<?php
if (!defined('ABSPATH')) exit;

/**
 * Meranie návštevnosti (Google Analytics 4) so súhlasom návštevníka.
 *
 * Nastavenia sú v NOX:ART → Nastavenia → "Meranie".
 */
define('NOX_ART_TRACKING_OPTION', 'nox_art_tracking');

function nox_art_tracking() {
    $o = get_option(NOX_ART_TRACKING_OPTION, []);
    $o = is_array($o) ? $o : [];
    return $o + ['ga_id' => ''];
}

/** ID merania GA4 (G-XXXXXXXX) alebo prázdny reťazec. */
function nox_art_ga_id() {
    $id = strtoupper(trim(nox_art_tracking()['ga_id']));
    return preg_match('/^G-[A-Z0-9]{6,}$/', $id) ? $id : '';
}

/**
 * Údaje pre skript: ID merania.
 */
function nox_art_tracking_localize() {
    if (!wp_script_is('nox-art-site-js', 'enqueued')) return;
    wp_localize_script('nox-art-site-js', 'NOX_SITE_TRACK', [
        'gaId' => nox_art_ga_id(),
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
