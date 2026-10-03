<?php
if (!defined('ABSPATH')) exit;

/**
 * Prihlasovacia stránka WordPressu (wp-login.php) v štýle festivalu:
 * pozadie z úvodnej strany, v strede zaoblená krémová karta s názvom
 * NOX:ART a sivým tlačidlom. Štýly sú v assets/login.css.
 */
function nox_art_login_enqueue() {
    $cesta = NOX_ART_DIR . 'assets/login.css';
    wp_enqueue_style(
        'nox-art-login',
        NOX_ART_URL . 'assets/login.css',
        [],
        file_exists($cesta) ? filemtime($cesta) : NOX_ART_VERSION
    );
}
// Neskorá priorita, aby štýl prebil iné pluginy upravujúce prihlásenie.
add_action('login_enqueue_scripts', 'nox_art_login_enqueue', 100);

// Logo vedie na úvod webu a má názov festivalu.
add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () { return 'NOX:ART'; });

// Doplnok pod názvom.
function nox_art_login_message($message) {
    return '<p class="nox-login-sub">Sídlisko Píly · 30.–31. 10. 2026</p>' . $message;
}
add_filter('login_message', 'nox_art_login_message', 5);

// Farba stavového riadka telefónu a písmo pri ťahaní.
function nox_art_login_head() {
    echo '<meta name="theme-color" content="#efeedc">' . "\n";
}
add_action('login_head', 'nox_art_login_head');
