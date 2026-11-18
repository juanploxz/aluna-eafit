<?php
/** Product schema and public read models. */

if (!defined('ABSPATH')) {
    exit;
}

function aluna_categories(): array
{
    return [
        'limpieza' => 'Limpieza',
        'hidratacion' => 'Hidratación',
        'tratamientos' => 'Tratamientos',
        'proteccion' => 'Protección solar',
    ];
}

function aluna_skin_types(): array
{
    return ['seca' => 'Seca', 'mixta' => 'Mixta', 'grasa' => 'Grasa', 'sensible' => 'Sensible', 'todas' => 'Todo tipo de piel'];
}

function aluna_meta_schema(): array
{
    return [
        'price' => ['type' => 'integer', 'default' => 0, 'sanitize_callback' => 'aluna_sanitize_price'],
        'size' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
        'skin' => ['type' => 'array', 'default' => ['todas'], 'sanitize_callback' => 'aluna_sanitize_skin', 'items' => ['type' => 'string']],
        'concern' => ['type' => 'string', 'default' => 'hidratacion', 'sanitize_callback' => 'aluna_sanitize_concern'],
        'ingredients' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field'],
        'usage' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field'],
        'featured' => ['type' => 'boolean', 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean'],
        'image' => ['type' => 'string', 'default' => 'cream.webp', 'sanitize_callback' => 'aluna_sanitize_image'],
        'tag' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
        'step' => ['type' => 'integer', 'default' => 2, 'sanitize_callback' => 'aluna_sanitize_step'],
    ];
}

function aluna_sanitize_skin($value): array
{
    $value = is_array($value) ? $value : [];
    $value = array_filter($value, 'is_string');
    $valid = array_values(array_unique(array_intersect(array_map('sanitize_key', $value), array_keys(aluna_skin_types()))));
    return $valid ?: ['todas'];
}

function aluna_sanitize_price($value): int
{
    if (!is_numeric($value)) {
        return 0;
    }
    return max(0, min(100000000, (int) $value));
}

function aluna_sanitize_concern($value): string
{
    if (!is_string($value)) {
        return 'hidratacion';
    }
    $value = sanitize_key((string) $value);
    return in_array($value, ['hidratacion', 'equilibrio', 'luminosidad', 'proteccion'], true) ? $value : 'hidratacion';
}

function aluna_sanitize_image($value): string
{
    if (!is_string($value)) {
        return 'cream.webp';
    }
    $value = sanitize_file_name(wp_basename((string) $value));
    return in_array($value, ['cleanser.webp', 'serum.webp', 'cream.webp', 'sunscreen.webp'], true) ? $value : 'cream.webp';
}

function aluna_sanitize_step($value): int
{
    if (!is_numeric($value)) {
        return 1;
    }
    return max(1, min(4, (int) $value));
}

function aluna_register_catalog(): void
{
    register_post_type('aluna_product', [
        'labels' => [
            'name' => 'Productos ALUNA', 'singular_name' => 'Producto', 'add_new_item' => 'Añadir producto',
            'edit_item' => 'Editar producto', 'new_item' => 'Nuevo producto', 'view_item' => 'Ver producto',
            'search_items' => 'Buscar productos', 'not_found' => 'No hay productos.', 'all_items' => 'Todos los productos',
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-products',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'producto', 'with_front' => false],
        'capability_type' => ['aluna_product', 'aluna_products'],
        'map_meta_cap' => true,
        // Product editing follows the normal WordPress editor permission model.
        'capabilities' => [
            'edit_posts' => 'edit_posts', 'edit_others_posts' => 'edit_others_posts', 'publish_posts' => 'publish_posts',
            'read_private_posts' => 'read_private_posts', 'delete_posts' => 'delete_posts',
            'delete_private_posts' => 'delete_private_posts', 'delete_published_posts' => 'delete_published_posts',
            'delete_others_posts' => 'delete_others_posts', 'edit_private_posts' => 'edit_private_posts',
            'edit_published_posts' => 'edit_published_posts', 'create_posts' => 'edit_posts',
        ],
    ]);
    register_taxonomy('aluna_category', ['aluna_product'], [
        'labels' => ['name' => 'Categorías', 'singular_name' => 'Categoría'],
        'public' => true, 'show_in_rest' => true, 'hierarchical' => true,
        'show_admin_column' => true, 'rewrite' => ['slug' => 'coleccion', 'with_front' => false],
    ]);
    foreach (aluna_meta_schema() as $key => $field) {
        $rest = ['schema' => ['type' => $field['type'], 'default' => $field['default']]];
        if (isset($field['items'])) {
            $rest['schema']['items'] = $field['items'];
        }
        register_post_meta('aluna_product', '_aluna_' . $key, [
            'type' => $field['type'], 'single' => true, 'default' => $field['default'],
            'show_in_rest' => $rest,
            'sanitize_callback' => $field['sanitize_callback'],
            'auth_callback' => static function ($allowed, $meta_key, $post_id): bool {
                return current_user_can('edit_post', (int) $post_id);
            },
        ]);
    }
}
add_action('init', 'aluna_register_catalog');

/** Public catalog values; unpublished content is never exposed. */
function aluna_product_data(int $post_id): ?array
{
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'aluna_product' || $post->post_status !== 'publish') {
        return null;
    }
    $terms = get_the_terms($post_id, 'aluna_category');
    $category = !is_wp_error($terms) && $terms ? $terms[0] : null;
    $data = [
        'id' => $post_id,
        'slug' => $post->post_name,
        'name' => get_the_title($post_id),
        'category' => $category ? $category->slug : 'hidratacion',
        'categoryLabel' => $category ? $category->name : 'Hidratación',
        'description' => wp_strip_all_tags($post->post_excerpt ?: $post->post_content),
        'url' => get_permalink($post_id),
    ];
    foreach (aluna_meta_schema() as $key => $field) {
        $raw = get_post_meta($post_id, '_aluna_' . $key, true);
        $data[$key] = call_user_func($field['sanitize_callback'], $raw !== '' ? $raw : $field['default']);
    }
    $data['imageUrl'] = get_the_post_thumbnail_url($post_id, 'large') ?: get_theme_file_uri('/assets/images/' . $data['image']);
    return $data;
}

function aluna_products(): array
{
    $posts = get_posts([
        'post_type' => 'aluna_product', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'], 'suppress_filters' => false,
    ]);
    return array_values(array_filter(array_map(static fn($post) => aluna_product_data((int) $post->ID), $posts)));
}
