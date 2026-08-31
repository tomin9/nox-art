<?php
if (!defined('ABSPATH')) exit;

function ghps_admin_menu() {
    add_options_page('GitHub Plugin Sync', 'GitHub Plugin Sync', 'manage_options', 'github-plugin-sync', 'ghps_render_settings_page');
}
add_action('admin_menu', 'ghps_admin_menu');

/* -------------------------------------------------------------------------
 * Ukladanie nastavení
 * ---------------------------------------------------------------------- */
function ghps_handle_save_settings() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('ghps_save_settings');

    $settings = ghps_get_settings();
    $existing_by_key = [];
    foreach ($settings['repos'] as $r) {
        $existing_by_key[$r['repo'] . '|' . $r['branch'] . '|' . $r['slug']] = $r;
    }

    $repos = [];
    $raw = isset($_POST['ghps_repos']) && is_array($_POST['ghps_repos']) ? $_POST['ghps_repos'] : [];
    foreach ($raw as $row) {
        $repo_name = isset($row['repo']) ? sanitize_text_field($row['repo']) : '';
        if ($repo_name === '') continue; // prázdny riadok (šablóna pre pridávanie) sa preskočí

        $branch = isset($row['branch']) && $row['branch'] !== '' ? sanitize_text_field($row['branch']) : 'main';
        $path = isset($row['path']) ? sanitize_text_field($row['path']) : '';
        $slug = isset($row['slug']) && $row['slug'] !== ''
            ? sanitize_title($row['slug'])
            : sanitize_title(preg_replace('#^.*/#', '', $repo_name));

        // sanitize_key() zmení text na malé písmená – ID generujeme rovno v tomto
        // tvare, aby sa neskôr pri porovnávaní (napr. v manuálnom syncu) nikdy
        // nerozišlo od hodnoty, ktorá prejde cez sanitize_key() z URL parametra.
        $id = isset($row['id']) && $row['id'] !== '' ? sanitize_key($row['id']) : sanitize_key(wp_generate_password(12, false));

        $entry = [
            'id' => $id,
            'repo' => $repo_name,
            'branch' => $branch,
            'path' => $path,
            'slug' => $slug,
            'activate' => !empty($row['activate']),
        ];

        // Zachovaj históriu posledného syncu, ak riadok už existoval.
        foreach ($settings['repos'] as $old) {
            if (sanitize_key($old['id']) === $id) {
                $entry['last_sync'] = $old['last_sync'] ?? '';
                $entry['last_result'] = $old['last_result'] ?? '';
                $entry['last_message'] = $old['last_message'] ?? '';
                $entry['last_commit'] = $old['last_commit'] ?? '';
                break;
            }
        }

        $repos[] = $entry;
    }

    $settings['repos'] = $repos;
    update_option(GHPS_OPTION, $settings);

    wp_safe_redirect(add_query_arg(['page' => 'github-plugin-sync', 'ghps_notice' => 'saved'], admin_url('options-general.php')));
    exit;
}
add_action('admin_post_ghps_save_settings', 'ghps_handle_save_settings');

function ghps_handle_regenerate_secret() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('ghps_regenerate_secret');

    $settings = ghps_get_settings();
    $settings['secret'] = wp_generate_password(40, false);
    update_option(GHPS_OPTION, $settings);

    wp_safe_redirect(add_query_arg(['page' => 'github-plugin-sync', 'ghps_notice' => 'secret'], admin_url('options-general.php')));
    exit;
}
add_action('admin_post_ghps_regenerate_secret', 'ghps_handle_regenerate_secret');

function ghps_handle_manual_sync() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('ghps_manual_sync');

    $repo_id = isset($_GET['repo_id']) ? sanitize_key($_GET['repo_id']) : '';
    $settings = ghps_get_settings();
    $found = null;
    foreach ($settings['repos'] as $r) {
        // sanitize_key() na oboch stranách – ošetruje aj staršie záznamy, ktorých
        // ID bolo uložené ešte pred normalizáciou na malé písmená.
        if (sanitize_key($r['id']) === $repo_id) { $found = $r; break; }
    }

    $notice = 'synced';
    if ($found) {
        $result = ghps_sync_repo($found);
        if (is_wp_error($result)) $notice = 'sync_error';
    } else {
        $notice = 'sync_error';
    }

    wp_safe_redirect(add_query_arg(['page' => 'github-plugin-sync', 'ghps_notice' => $notice], admin_url('options-general.php')));
    exit;
}
add_action('admin_post_ghps_manual_sync', 'ghps_handle_manual_sync');

/* -------------------------------------------------------------------------
 * Vykreslenie stránky nastavení
 * ---------------------------------------------------------------------- */
function ghps_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    $settings = ghps_get_settings();
    $repos = $settings['repos'];
    $notice = isset($_GET['ghps_notice']) ? sanitize_key($_GET['ghps_notice']) : '';
    ?>
    <div class="wrap">
        <h1>GitHub Plugin Sync</h1>
        <p>Pridaš verejné GitHub repozitáre, ktoré sa majú automaticky nainštalovať/aktualizovať ako WordPress pluginy. Pri každom pushi na sledovanú vetvu pošle GitHub webhook signál a plugin sa okamžite stiahne z GitHubu a nahradí súčasnú verziu.</p>

        <?php if ($notice === 'saved'): ?><div class="notice notice-success is-dismissible"><p>Nastavenia uložené.</p></div><?php endif; ?>
        <?php if ($notice === 'secret'): ?><div class="notice notice-success is-dismissible"><p>Webhook secret bol vygenerovaný nanovo – nezabudni ho aktualizovať aj v nastaveniach GitHub webhooku.</p></div><?php endif; ?>
        <?php if ($notice === 'synced'): ?><div class="notice notice-success is-dismissible"><p>Synchronizácia prebehla.</p></div><?php endif; ?>
        <?php if ($notice === 'sync_error'): ?><div class="notice notice-error is-dismissible"><p>Synchronizácia zlyhala – pozri stĺpec „Stav“ pri repozitári.</p></div><?php endif; ?>

        <h2>Webhook</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Webhook URL</th>
                <td><input type="text" readonly class="regular-text" style="width:520px" value="<?php echo esc_attr(ghps_webhook_url()); ?>" onclick="this.select()"></td>
            </tr>
            <tr>
                <th scope="row">Webhook secret</th>
                <td>
                    <input type="text" readonly class="regular-text" style="width:520px" value="<?php echo esc_attr($settings['secret']); ?>" onclick="this.select()">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-left:6px">
                        <?php wp_nonce_field('ghps_regenerate_secret'); ?>
                        <input type="hidden" name="action" value="ghps_regenerate_secret">
                        <button type="submit" class="button" onclick="return confirm('Vygenerovať nový secret? Starý prestane fungovať, treba ho zmeniť aj v GitHub webhooku.');">Vygenerovať nový</button>
                    </form>
                </td>
            </tr>
        </table>
        <p class="description">
            V GitHub repozitári choď do <strong>Settings → Webhooks → Add webhook</strong>. Payload URL a Secret vlož z polí vyššie,
            Content type nastav na <code>application/json</code> a v „Which events would you like to trigger this webhook?“ nechaj len <strong>Just the push event</strong>.
        </p>

        <h2>Sledované repozitáre</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ghps_save_settings'); ?>
            <input type="hidden" name="action" value="ghps_save_settings">

            <table class="widefat striped" id="ghps-repo-table">
                <thead>
                    <tr>
                        <th>Repozitár (owner/repo)</th>
                        <th>Vetva</th>
                        <th>Cesta k pluginu v repe</th>
                        <th>Cieľový priečinok (slug)</th>
                        <th>Auto-aktivovať</th>
                        <th>Posledný sync</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repos as $i => $repo): ?>
                        <?php ghps_render_repo_row($repo, $i); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><button type="button" id="ghps-add-row" class="button">+ Pridať repozitár</button></p>
            <p><button type="submit" class="button button-primary">Uložiť nastavenia</button></p>
        </form>

        <template id="ghps-row-template">
            <?php ghps_render_repo_row(['id' => '', 'repo' => '', 'branch' => 'main', 'path' => '', 'slug' => '', 'activate' => true], '__INDEX__'); ?>
        </template>
    </div>
    <script>
    (function(){
        var tbody = document.querySelector('#ghps-repo-table tbody');
        var template = document.getElementById('ghps-row-template');
        var addBtn = document.getElementById('ghps-add-row');
        var counter = <?php echo (int) count($repos); ?>;

        addBtn.addEventListener('click', function(){
            var html = template.innerHTML.replace(/__INDEX__/g, counter++);
            var wrapper = document.createElement('tbody');
            wrapper.innerHTML = html;
            tbody.appendChild(wrapper.firstElementChild);
        });

        tbody.addEventListener('click', function(e){
            if (e.target && e.target.classList.contains('ghps-remove-row')) {
                e.preventDefault();
                e.target.closest('tr').remove();
            }
        });
    })();
    </script>
    <?php
}

function ghps_render_repo_row($repo, $index) {
    $sync_admin_url = wp_nonce_url(
        add_query_arg(['action' => 'ghps_manual_sync', 'repo_id' => $repo['id']], admin_url('admin-post.php')),
        'ghps_manual_sync'
    );
    ?>
    <tr>
        <td><input type="text" name="ghps_repos[<?php echo esc_attr($index); ?>][repo]" value="<?php echo esc_attr($repo['repo']); ?>" placeholder="owner/repo" class="regular-text"></td>
        <td><input type="text" name="ghps_repos[<?php echo esc_attr($index); ?>][branch]" value="<?php echo esc_attr($repo['branch'] ?: 'main'); ?>" placeholder="main" style="width:100px"></td>
        <td><input type="text" name="ghps_repos[<?php echo esc_attr($index); ?>][path]" value="<?php echo esc_attr($repo['path'] ?? ''); ?>" placeholder="(prázdne = koreň repa)" class="regular-text"></td>
        <td><input type="text" name="ghps_repos[<?php echo esc_attr($index); ?>][slug]" value="<?php echo esc_attr($repo['slug']); ?>" placeholder="napr. nox-art-festival" class="regular-text"></td>
        <td style="text-align:center"><input type="checkbox" name="ghps_repos[<?php echo esc_attr($index); ?>][activate]" <?php checked(!empty($repo['activate'])); ?>></td>
        <td>
            <?php if (!empty($repo['id']) && !empty($repo['last_sync'])): ?>
                <span title="<?php echo esc_attr($repo['last_message'] ?? ''); ?>">
                    <?php echo $repo['last_result'] === 'ok' ? '✅' : '⚠️'; ?>
                    <?php echo esc_html($repo['last_sync']); ?>
                    <?php if (!empty($repo['last_commit'])): ?> (<code><?php echo esc_html($repo['last_commit']); ?></code>)<?php endif; ?>
                </span>
            <?php else: ?>
                <span class="description">zatiaľ nesynchronizované</span>
            <?php endif; ?>
        </td>
        <td>
            <input type="hidden" name="ghps_repos[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($repo['id']); ?>">
            <?php if (!empty($repo['id'])): ?>
                <a class="button" href="<?php echo esc_url($sync_admin_url); ?>">Synchronizovať teraz</a>
            <?php endif; ?>
            <a href="#" class="button ghps-remove-row">Odstrániť</a>
        </td>
    </tr>
    <?php
}
