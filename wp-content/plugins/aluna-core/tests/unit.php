<?php
/** Standalone tests for sanitizers and identity. Execute: php tests/unit.php */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    http_response_code(404);
    exit;
}

define('ABSPATH', __DIR__ . '/');
function add_action(...$args): void {}
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function sanitize_file_name($value): string { return preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $value); }
function wp_basename($value): string { return basename(str_replace('\\', '/', $value)); }
function absint($value): int { return abs((int) $value); }
function wp_generate_uuid4(): string { return 'unit-test-uuid-' . bin2hex(random_bytes(8)); }

require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/identity.php';

$tests = 0;
function expect($actual, $expected, string $message): void
{
    global $tests;
    $tests++;
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

foreach (['10.0.0.1', '10.255.255.255', '172.16.0.1', '172.31.255.255', '192.168.1.1'] as $ip) {
    expect(aluna_is_private_ipv4($ip), true, 'Accept valid RFC1918: ' . $ip);
}
foreach (['172.15.255.255', '172.32.0.1', '127.0.0.1', '0.0.0.0', '8.8.8.8', '169.254.1.1', '100.64.0.1', '::1', 'localhost', '10.0.0.256', "10.1.2.3\r\nInjected: yes", '', null, ['10.0.0.1']] as $ip) {
    expect(aluna_is_private_ipv4($ip), false, 'Reject non-private/invalid candidate');
}
expect(aluna_sanitize_skin(['seca', 'mixta', 'seca', '<script>', ['bad']]), ['seca', 'mixta'], 'Deduplicate and reject invalid skin types');
expect(aluna_sanitize_skin([]), ['todas'], 'Default for empty skin type');
expect(aluna_sanitize_skin('seca'), ['todas'], 'Reject wrong skin value type');
expect(aluna_sanitize_concern('equilibrio'), 'equilibrio', 'Keep supported concern');
expect(aluna_sanitize_concern('arbitrary'), 'hidratacion', 'Reject arbitrary concern');
expect(aluna_sanitize_concern(['equilibrio']), 'hidratacion', 'Reject malformed concern');
expect(aluna_sanitize_step(100), 4, 'Upper step bound');
expect(aluna_sanitize_step(0), 1, 'Lower step bound');
expect(aluna_sanitize_step(-2), 1, 'Negative step clamps to first step');
expect(aluna_sanitize_price(-400), 0, 'Do not turn negative price into a positive price');
expect(aluna_sanitize_price(['bad']), 0, 'Reject malformed price');
expect(aluna_sanitize_price(100000001), 100000000, 'Upper price bound');
expect(aluna_sanitize_image('../../secret.php'), 'cream.webp', 'Reject arbitrary asset');
expect(aluna_sanitize_image('serum.webp'), 'serum.webp', 'Keep allowed asset');
putenv('ALUNA_NODE_IP=10.20.1.4');
$_SERVER['SERVER_ADDR'] = '10.20.1.5';
expect(aluna_resolve_node_ip(), '10.20.1.4', 'Per-instance environment precedence');
putenv('ALUNA_NODE_IP=8.8.8.8');
expect(aluna_resolve_node_ip(), '10.20.1.5', 'Invalid environment falls back to server address');
$identity = aluna_server_identity();
expect(aluna_server_identity(), $identity, 'Request identity remains stable within request');
expect($identity['ip'], '10.20.1.5', 'Identity reflects actual selected IP');
expect(isset($identity['request_id'], $identity['time']), true, 'Identity contains request and timestamp');
expect(str_contains(aluna_no_cache_headers()['Cache-Control'], 'no-store'), true, 'No store cache policy');
fwrite(STDOUT, $tests . ' assertions passed.' . PHP_EOL);
