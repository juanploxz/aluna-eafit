<?php
/**
 * Plugin Name: ALUNA Core
 * Description: Catálogo editorial y diagnóstico por petición para ALUNA.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: ALUNA
 * License: GPL-2.0-or-later
 * Text Domain: aluna
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALUNA_CORE_VERSION', '1.0.0');
define('ALUNA_CORE_PATH', plugin_dir_path(__FILE__));

require_once ALUNA_CORE_PATH . 'includes/catalog.php';
require_once ALUNA_CORE_PATH . 'includes/admin.php';
require_once ALUNA_CORE_PATH . 'includes/identity.php';
require_once ALUNA_CORE_PATH . 'includes/seed.php';

register_activation_hook(__FILE__, static function (): void {
    aluna_register_catalog();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});
