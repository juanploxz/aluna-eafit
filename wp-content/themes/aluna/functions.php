<?php
/** ALUNA presentation layer. Business content lives in aluna-core. */
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor.css');
});

function aluna_asset(string $path): string { return get_template_directory_uri() . '/assets/' . ltrim($path, '/'); }
function aluna_price(int $value): string { return '$' . number_format($value, 0, ',', '.'); }
/** Explicit homepage-only diagnostic view for the course's load-balancing evidence. */
function aluna_diagnostics_enabled(): bool {
    return is_front_page()
        && isset($_GET['aluna-diagnostico'])
        && is_string($_GET['aluna-diagnostico'])
        && wp_unslash($_GET['aluna-diagnostico']) === '1';
}
function aluna_copy(string $key): string {
    $defaults = [
        'hero_eyebrow' => 'BIENESTAR EN LO COTIDIANO',
        'hero_title' => 'Tu piel. Tu ritmo.',
        'hero_accent' => 'Tu ritual.',
        'hero_description' => 'Dale espacio a lo que te hace bien. Descubre una forma más simple de cuidar tu piel, todos los días.',
        'about_title' => 'Menos pasos. Más intención.',
        'about_description' => 'Creemos que el cuidado personal empieza por escucharte. Por eso reunimos esenciales fáciles de entender y de incorporar a tu día, sin rutinas imposibles.',
        'about_second' => 'ALUNA es la propuesta de cuidado personal de XYZ S.A. para Colombia. Un encuentro entre texturas, ingredientes y pequeños momentos para ti.',
    ];
    return (string) get_theme_mod('aluna_' . $key, $defaults[$key] ?? '');
}

add_action('customize_register', function ($customizer) {
    $customizer->add_section('aluna_content', ['title' => 'ALUNA · Contenido de inicio', 'priority' => 30]);
    foreach (['hero_eyebrow'=>'Portada: etiqueta', 'hero_title'=>'Portada: título', 'hero_accent'=>'Portada: título en cursiva', 'hero_description'=>'Portada: descripción', 'about_title'=>'Nosotros: título', 'about_description'=>'Nosotros: párrafo 1', 'about_second'=>'Nosotros: párrafo 2'] as $key=>$label) {
        $customizer->add_setting('aluna_' . $key, ['default'=>aluna_copy($key), 'sanitize_callback'=>'sanitize_textarea_field', 'transport'=>'refresh']);
        $customizer->add_control('aluna_' . $key, ['label'=>$label, 'section'=>'aluna_content', 'type'=>in_array($key, ['hero_description','about_description','about_second'], true) ? 'textarea' : 'text']);
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('aluna', aluna_asset('css/app.css'), [], '1.0.1');
    wp_enqueue_script('aluna-logic', aluna_asset('js/logic.js'), [], '1.0.1', ['strategy'=>'defer','in_footer'=>true]);
    wp_enqueue_script('aluna', aluna_asset('js/app.js'), ['aluna-logic'], '1.0.1', ['strategy'=>'defer','in_footer'=>true]);
});

function aluna_icon(string $name, string $class = ''): void {
    $paths = [
        'arrow'=>'<path d="M4 12h16M14 6l6 6-6 6"/>',
        'diagonal'=>'<path d="M6 18 18 6M6 6h12v12"/>',
        'heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'close'=>'<path d="m6 6 12 12M6 18 18 6"/>',
        'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'pin'=>'<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 1v2M12 21v2M1 12h2M21 12h2M4 4l2 2M18 18l2 2M4 20l2-2M18 6l2-2"/>',
        'leaf'=>'<path d="M20 3C8 1 1 8 5 16c8 5 16-1 15-13ZM4 21 15 10"/>',
        'drop'=>'<path d="M12 2C10 7 5 10 5 15a7 7 0 0 0 14 0c0-5-5-8-7-13Z"/>',
        'bloom'=>'<path d="M12 2v20M2 12h20M5 5l14 14M5 19 19 5"/>',
    ];
    echo '<svg class="icon ' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}

function aluna_card(array $product): void {
    $p = $product;
    include get_template_directory() . '/template-parts/product-card.php';
}

add_action('wp_head', function () {
    if (is_front_page()) {
        echo '<meta name="description" content="Descubre ALUNA: esenciales de cuidado personal, tu guía de rutina y presencia en Medellín, Bogotá y Bucaramanga.">';
        echo '<link rel="preload" as="image" href="' . esc_url(aluna_asset('images/hero.webp')) . '">';
    }
    echo '<meta name="theme-color" content="#f6f5ef">';
    if (!has_site_icon()) {
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(aluna_asset('images/favicon.svg')) . '">';
    }
});

add_action('admin_notices', function () {
    if (current_user_can('activate_plugins') && !function_exists('aluna_products')) {
        echo '<div class="notice notice-warning"><p>ALUNA necesita el plugin ALUNA Core para administrar y mostrar el catálogo.</p></div>';
    }
});
