<?php
/**
 * Plugin Name: NOX:ART Festival
 * Description: Podstránka festivalu NOX:ART – miesta, kde je možné vidieť diela, popisky diel a program festivalu. Interaktívna mapa cez shortcode [nox_art]. Obsah sa spravuje priamo vo WordPress administrácii (žiadna externá databáza).
 * Version: 1.0.0
 * Author: Ars Preuge
 * Text Domain: nox-art-festival
 */

if (!defined('ABSPATH')) exit;

define('NOX_ART_VERSION', '1.0.0');
define('NOX_ART_DIR', plugin_dir_path(__FILE__));
define('NOX_ART_URL', plugin_dir_url(__FILE__));

/**
 * Načíta obsah assetu (JS/CSS) z priečinka assets/ tohto pluginu.
 */
function nox_art_asset($filename) {
    $path = NOX_ART_DIR . 'assets/' . $filename;
    return file_exists($path) ? file_get_contents($path) : '';
}

require_once NOX_ART_DIR . 'includes/post-types.php';
require_once NOX_ART_DIR . 'includes/meta-boxes.php';
require_once NOX_ART_DIR . 'includes/admin-columns.php';
require_once NOX_ART_DIR . 'includes/data.php';
require_once NOX_ART_DIR . 'includes/shortcode.php';

/**
 * Pri prvej aktivácii treba "preplaviť" pravidlá permalinkov, aby fungovali
 * archívy/detaily custom post types (ak sa niekedy využijú samostatné URL).
 */
function nox_art_activate() {
    nox_art_register_post_types();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'nox_art_activate');

function nox_art_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'nox_art_deactivate');
