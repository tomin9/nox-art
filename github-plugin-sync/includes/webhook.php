<?php
if (!defined('ABSPATH') ) exit;

function ghps_register_webhook_route() {
    register_rest_route('ghps/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'ghps_handle_webhook',
        'permission_callback' => '__return_true', // overuje sa manuálne cez HMAC podpis nižšie
    ]);
}
add_action('rest_api_init', 'ghps_register_webhook_route');

function ghps_webhook_url() {
    return rest_url('ghps/v1/webhook');
}

/**
 * Spracuje GitHub webhook "push" event. Bezpečnosť: GitHub k requestu pridá
 * hlavičku X-Hub-Signature-256 = HMAC-SHA256(telo requestu, secret) – bez
 * platného podpisu (t.j. bez znalosti secretu z nastavení) sa nič nespustí.
 */
function ghps_handle_webhook(WP_REST_Request $request) {
    $settings = ghps_get_settings();
    $secret = $settings['secret'];

    if (!$secret) {
        return new WP_REST_Response(['error' => 'Webhook secret nie je nastavený.'], 500);
    }

    $body = $request->get_body();
    $signature = $request->get_header('x_hub_signature_256');
    if (!$signature) {
        return new WP_REST_Response(['error' => 'Chýba podpis požiadavky.'], 401);
    }
    $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
    if (!hash_equals($expected, $signature)) {
        return new WP_REST_Response(['error' => 'Neplatný podpis požiadavky.'], 401);
    }

    $event = $request->get_header('x_github_event');
    if ($event === 'ping') {
        return new WP_REST_Response(['ok' => true, 'message' => 'pong'], 200);
    }
    if ($event !== 'push') {
        return new WP_REST_Response(['ok' => true, 'message' => 'Event "' . $event . '" sa ignoruje.'], 200);
    }

    $payload = json_decode($body, true);
    if (!is_array($payload) || empty($payload['repository']['full_name']) || empty($payload['ref'])) {
        return new WP_REST_Response(['error' => 'Neočakávaný tvar payloadu.'], 400);
    }

    $full_name = $payload['repository']['full_name'];
    $ref = $payload['ref']; // napr. "refs/heads/main"
    $commit = $payload['after'] ?? '';

    $repo = ghps_find_repo_by_fullname($full_name);
    if (!$repo) {
        return new WP_REST_Response(['ok' => true, 'message' => 'Repozitár "' . $full_name . '" nie je sledovaný.'], 200);
    }

    $expected_ref = 'refs/heads/' . (trim($repo['branch']) ?: 'main');
    if ($ref !== $expected_ref) {
        return new WP_REST_Response(['ok' => true, 'message' => 'Push na "' . $ref . '" sa ignoruje (sleduje sa ' . $expected_ref . ').'], 200);
    }

    $result = ghps_sync_repo($repo, $commit);
    if (is_wp_error($result)) {
        return new WP_REST_Response(['error' => $result->get_error_message()], 500);
    }

    return new WP_REST_Response(['ok' => true, 'message' => 'Plugin "' . $repo['slug'] . '" bol synchronizovaný.'], 200);
}
