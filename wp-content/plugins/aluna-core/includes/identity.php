<?php
/** Request identity is calculated on this PHP node, never in the browser. */

if (!defined('ABSPATH')) {
    exit;
}

function aluna_is_private_ipv4($ip): bool
{
    if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $octets = array_map('intval', explode('.', $ip));
    return $octets[0] === 10
        || ($octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31)
        || ($octets[0] === 192 && $octets[1] === 168);
}

function aluna_resolve_node_ip(): ?string
{
    $candidates = [
        defined('ALUNA_NODE_IP') ? ALUNA_NODE_IP : null,
        getenv('ALUNA_NODE_IP') ?: null,
        $_SERVER['SERVER_ADDR'] ?? null,
    ];
    foreach ($candidates as $candidate) {
        if (aluna_is_private_ipv4($candidate)) {
            return $candidate;
        }
    }
    $hostname = gethostname();
    $resolved = $hostname ? gethostbyname($hostname) : false;
    return aluna_is_private_ipv4($resolved) ? $resolved : null;
}

function aluna_server_identity(): array
{
    static $identity = null;
    if ($identity === null) {
        $identity = [
            'ip' => aluna_resolve_node_ip(),
            'request_id' => wp_generate_uuid4(),
            'time' => gmdate('c'),
        ];
    }
    return $identity;
}

function aluna_no_cache_headers(): array
{
    return [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
        'Pragma' => 'no-cache',
        'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT',
        'Surrogate-Control' => 'no-store',
        'CDN-Cache-Control' => 'no-store',
    ];
}

function aluna_send_node_headers(): void
{
    if (!is_front_page()) {
        return;
    }
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (headers_sent()) {
        return;
    }
    foreach (aluna_no_cache_headers() as $name => $value) {
        header($name . ': ' . $value, true);
    }
    $identity = aluna_server_identity();
    header('X-Aluna-Node: ' . ($identity['ip'] ?? 'unavailable'), true);
    header('X-Aluna-Request-ID: ' . $identity['request_id'], true);
}
add_action('template_redirect', 'aluna_send_node_headers', 0);

function aluna_rest_response(array $payload, int $status = 200): WP_REST_Response
{
    $identity = aluna_server_identity();
    return new WP_REST_Response($payload, $status, array_merge(aluna_no_cache_headers(), [
        'X-Aluna-Node' => $identity['ip'] ?? 'unavailable',
        'X-Aluna-Request-ID' => $identity['request_id'],
    ]));
}

add_action('rest_api_init', static function (): void {
    register_rest_route('aluna/v1', '/node', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => static fn() => aluna_rest_response(aluna_server_identity()),
    ]);
    register_rest_route('aluna/v1', '/health', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => static function (): WP_REST_Response {
            global $wpdb;
            $previous = $wpdb->suppress_errors(true);
            $ready = (string) $wpdb->get_var('SELECT 1') === '1';
            $wpdb->suppress_errors($previous);
            return aluna_rest_response(['status' => $ready ? 'ok' : 'unavailable'], $ready ? 200 : 503);
        },
    ]);
});
