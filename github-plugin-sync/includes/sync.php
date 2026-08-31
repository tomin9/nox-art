<?php
if (!defined('ABSPATH')) exit;

/**
 * Nájde konfiguráciu repozitára podľa "owner/repo" (case-insensitive).
 */
function ghps_find_repo_by_fullname($full_name) {
    $settings = ghps_get_settings();
    foreach ($settings['repos'] as $repo) {
        if (strcasecmp(trim($repo['repo']), trim($full_name)) === 0) return $repo;
    }
    return null;
}

/**
 * Zapíše výsledok posledného pokusu o sync pre daný repo (id) – zobrazuje sa
 * v administrácii, aby bolo hneď vidno, či posledný webhook prešiel.
 */
function ghps_record_status($repo_id, $result, $message, $commit = '') {
    $settings = ghps_get_settings();
    foreach ($settings['repos'] as &$repo) {
        if ($repo['id'] === $repo_id) {
            $repo['last_sync'] = current_time('mysql');
            $repo['last_result'] = $result;
            $repo['last_message'] = $message;
            if ($commit) $repo['last_commit'] = substr($commit, 0, 10);
            break;
        }
    }
    unset($repo);
    update_option(GHPS_OPTION, $settings);
}

/**
 * Stiahne vetvu repozitára ako zip z GitHubu a nahradí ňou obsah priečinka
 * pluginu vo wp-content/plugins/{slug}. Funguje len pre verejné repozitáre
 * (žiadna autentifikácia).
 *
 * @param array $repo ['id','repo' => 'owner/name', 'branch', 'path', 'slug', 'activate']
 * @return true|WP_Error
 */
function ghps_sync_repo($repo, $commit = '') {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $full_name = trim($repo['repo']);
    $branch = trim($repo['branch']) ?: 'main';
    $path = trim($repo['path'] ?? '', "/ \t\n\r\0\x0B");
    $slug = sanitize_title($repo['slug'] ?: preg_replace('#^.*/#', '', $full_name));

    if (!$full_name || strpos($full_name, '/') === false) {
        $err = new WP_Error('ghps_bad_repo', 'Neplatný formát repozitára (očakávam "owner/repo").');
        ghps_record_status($repo['id'], 'error', $err->get_error_message());
        return $err;
    }

    $zip_url = "https://github.com/{$full_name}/archive/refs/heads/{$branch}.zip";
    $tmp_file = download_url($zip_url);
    if (is_wp_error($tmp_file)) {
        ghps_record_status($repo['id'], 'error', 'Stiahnutie zlyhalo: ' . $tmp_file->get_error_message());
        return $tmp_file;
    }

    if (!function_exists('WP_Filesystem')) require_once ABSPATH . 'wp-admin/includes/file.php';
    $fs_ready = WP_Filesystem();
    global $wp_filesystem;
    if (!$fs_ready || !$wp_filesystem) {
        @unlink($tmp_file);
        $msg = 'Nepodarilo sa pripojiť k súborovému systému';
        if ($wp_filesystem && !empty($wp_filesystem->errors) && is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->has_errors()) {
            $msg .= ': ' . $wp_filesystem->errors->get_error_message();
        } else {
            $msg .= ' (server pravdepodobne vyžaduje FTP/SSH prístupové údaje pre priamy zápis súborov namiesto "direct" metódy).';
        }
        $err = new WP_Error('ghps_no_filesystem', $msg);
        ghps_record_status($repo['id'], 'error', $err->get_error_message());
        return $err;
    }

    $work_dir = trailingslashit(get_temp_dir()) . 'ghps-' . $slug . '-' . wp_generate_password(6, false);
    $unzipped = unzip_file($tmp_file, $work_dir);
    @unlink($tmp_file);
    if (is_wp_error($unzipped)) {
        ghps_record_status($repo['id'], 'error', 'Rozbalenie zlyhalo: ' . $unzipped->get_error_message());
        return $unzipped;
    }

    // GitHub zip vždy obsahuje presne jeden koreňový priečinok "repo-branch".
    $entries = array_values(array_diff((array) scandir($work_dir), ['.', '..']));
    if (count($entries) !== 1 || !$wp_filesystem->is_dir($work_dir . '/' . $entries[0])) {
        $wp_filesystem->delete($work_dir, true);
        $err = new WP_Error('ghps_unexpected_zip', 'Neočakávaný obsah zip archívu z GitHubu.');
        ghps_record_status($repo['id'], 'error', $err->get_error_message());
        return $err;
    }
    $repo_root = $work_dir . '/' . $entries[0];
    $source_dir = $path ? $repo_root . '/' . $path : $repo_root;

    if (!$wp_filesystem->is_dir($source_dir)) {
        $wp_filesystem->delete($work_dir, true);
        $err = new WP_Error('ghps_missing_path', 'Cesta "' . $path . '" v repozitári neexistuje.');
        ghps_record_status($repo['id'], 'error', $err->get_error_message());
        return $err;
    }

    $target_dir = trailingslashit(WP_PLUGIN_DIR) . $slug;
    if ($wp_filesystem->is_dir($target_dir)) {
        $wp_filesystem->delete($target_dir, true);
    }
    $copied = copy_dir($source_dir, $target_dir);
    $wp_filesystem->delete($work_dir, true);

    if (is_wp_error($copied)) {
        ghps_record_status($repo['id'], 'error', 'Kopírovanie zlyhalo: ' . $copied->get_error_message());
        return $copied;
    }

    $main_file = ghps_find_plugin_main_file($target_dir, $slug);
    if (!empty($repo['activate']) && $main_file && !is_plugin_active($main_file)) {
        $activated = activate_plugin($main_file);
        if (is_wp_error($activated)) {
            ghps_record_status($repo['id'], 'ok', 'Nainštalované, ale aktivácia zlyhala: ' . $activated->get_error_message(), $commit);
            return true;
        }
    }

    ghps_record_status($repo['id'], 'ok', 'Nainštalované z vetvy "' . $branch . '".', $commit);
    return true;
}

/**
 * Nájde vstupný súbor pluginu (s hlavičkou "Plugin Name:") v novo
 * skopírovanom priečinku, aby sa dal poskladať plugin_basename pre aktiváciu.
 */
function ghps_find_plugin_main_file($target_dir, $slug) {
    $files = glob($target_dir . '/*.php');
    if (!$files) return null;
    foreach ($files as $file) {
        $data = get_plugin_data($file, false, false);
        if (!empty($data['Name'])) {
            return $slug . '/' . basename($file);
        }
    }
    return null;
}
