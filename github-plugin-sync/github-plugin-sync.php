<?php
/**
 * Plugin Name: GitHub Plugin Sync
 * Description: Zadáš verejné GitHub repozitáre (a cestu k pluginu v nich) – pri každom pushi na sledovanú vetvu GitHub webhook okamžite pošle WordPressu signál a plugin sa automaticky stiahne a nainštaluje/aktualizuje z GitHubu. Bez FTP, bez ručného nahrávania zipov.
 * Version: 1.0.0
 * Author: Ars Preuge
 * Text Domain: github-plugin-sync
 */

if (!defined('ABSPATH')) exit;

define('GHPS_VERSION', '1.0.0');
define('GHPS_DIR', plugin_dir_path(__FILE__));
define('GHPS_OPTION', 'ghps_settings');

require_once GHPS_DIR . 'includes/sync.php';
require_once GHPS_DIR . 'includes/webhook.php';
require_once GHPS_DIR . 'includes/settings-page.php';

/**
 * Predvolené nastavenia: náhodný webhook secret sa vygeneruje hneď pri
 * aktivácii, aby ho bolo čo vložiť do GitHub webhooku bez ďalšieho kroku.
 */
function ghps_activate() {
    $settings = get_option(GHPS_OPTION);
    if (!$settings || empty($settings['secret'])) {
        $settings = wp_parse_args(is_array($settings) ? $settings : [], [
            'secret' => wp_generate_password(40, false),
            'repos' => [],
        ]);
        update_option(GHPS_OPTION, $settings);
    }
}
register_activation_hook(__FILE__, 'ghps_activate');

function ghps_get_settings() {
    $defaults = ['secret' => '', 'repos' => []];
    $settings = get_option(GHPS_OPTION, $defaults);
    return wp_parse_args(is_array($settings) ? $settings : [], $defaults);
}
