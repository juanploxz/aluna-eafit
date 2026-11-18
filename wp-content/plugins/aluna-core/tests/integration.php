<?php
/**
 * Real WordPress integration checks, for a disposable test database only.
 * After loading WordPress: define('ALUNA_TESTING', true); require this file;
 * $results = aluna_run_integration_checks();
 */
if (!defined('ABSPATH') || !defined('ALUNA_TESTING') || ALUNA_TESTING !== true) {
    http_response_code(404);
    exit;
}

function aluna_run_integration_checks(): array
{
    $results = [];
    $check = static function (bool $ok, string $message) use (&$results): void {
        $results[] = ['name' => $message, 'pass' => $ok];
        if (!$ok) {
            throw new RuntimeException($message);
        }
    };
    $check(post_type_exists('aluna_product'), 'Product post type registered');
    $check(taxonomy_exists('aluna_category'), 'Product taxonomy registered');
    $first = aluna_seed_catalog();
    $check(!is_wp_error($first), 'Sample catalog import succeeds');
    $second = aluna_seed_catalog();
    $check(!is_wp_error($second) && $second['created'] === 0 && $second['skipped'] === 8, 'Import is idempotent');
    $check(count(aluna_products()) >= 8, 'Catalog has eight published samples');

    $original_user = get_current_user_id();
    $original_post = $_POST;
    $id = wp_insert_post([
        'post_type' => 'aluna_product', 'post_status' => 'draft', 'post_title' => 'Integration test product',
        'post_excerpt' => '<b>Clean description</b>',
    ], true);
    $check(!is_wp_error($id), 'Can create a draft product');
    try {
        $check(aluna_product_data($id) === null, 'Draft products are not exposed');
        update_post_meta($id, '_aluna_price', -50);
        update_post_meta($id, '_aluna_skin', ['seca', 'invalid', ['nested']]);
        update_post_meta($id, '_aluna_image', '../../wp-config.php');
        update_post_meta($id, '_aluna_step', 99);
        wp_update_post(['ID' => $id, 'post_status' => 'publish']);
        $data = aluna_product_data($id);
        $check($data !== null, 'Published products are exposed');
        $check($data['price'] === 0, 'Negative price is rejected');
        $check($data['skin'] === ['seca'], 'Unknown and nested skin values are rejected');
        $check($data['image'] === 'cream.webp', 'Image path traversal is rejected');
        $check($data['step'] === 4, 'Routine step is bounded');
        $check($data['description'] === 'Clean description', 'Public summary removes markup');

        $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
        $check(count($admins) === 1, 'Test administrator exists');
        wp_set_current_user((int) $admins[0]);
        $_POST = ['aluna_product_nonce' => 'invalid', 'aluna' => ['price' => 123456]];
        do_action('save_post_aluna_product', $id);
        $check((int) get_post_meta($id, '_aluna_price', true) === 0, 'Invalid nonce cannot change product details');
        $_POST['aluna_product_nonce'] = wp_create_nonce('aluna_save_product');
        do_action('save_post_aluna_product', $id);
        $check((int) get_post_meta($id, '_aluna_price', true) === 123456, 'Authorized editor can persist product details');

        wp_set_current_user(0);
        $request = new WP_REST_Request('POST', '/wp/v2/aluna_product/' . $id);
        $request->set_param('title', 'Unauthorized change');
        $response = rest_do_request($request);
        $check(in_array($response->get_status(), [401, 403], true), 'Anonymous catalog writes are denied');
        $node = rest_do_request(new WP_REST_Request('GET', '/aluna/v1/node'));
        $check($node->get_status() === 200, 'Node endpoint is available');
        $check(isset($node->get_data()['request_id']), 'Node response identifies the request');
        $check(str_contains($node->get_headers()['Cache-Control'] ?? '', 'no-store'), 'Node endpoint cannot be cached');
        $health = rest_do_request(new WP_REST_Request('GET', '/aluna/v1/health'));
        $check($health->get_status() === 200 && $health->get_data()['status'] === 'ok', 'Health endpoint checks database connectivity');
    } finally {
        $_POST = $original_post;
        wp_set_current_user($original_user);
        wp_delete_post($id, true);
    }
    return $results;
}
