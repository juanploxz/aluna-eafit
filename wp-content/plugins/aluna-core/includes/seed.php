<?php
/** Explicit, idempotent sample content import. Never overwrites editor changes. */

if (!defined('ABSPATH')) {
    exit;
}

function aluna_seed_data(): array
{
    return [
        [
            'slug' => 'limpiador-esencial', 'name' => 'Limpiador esencial', 'category' => 'limpieza',
            'description' => 'Un comienzo suave. Gel de limpieza ligero para retirar las impurezas del día sin una sensación tirante.',
            'price' => 68000, 'size' => '150 ml', 'skin' => ['todas'], 'concern' => 'equilibrio',
            'ingredients' => 'Glicerina · extracto de avena · pantenol.',
            'usage' => 'Masajea una pequeña cantidad sobre el rostro húmedo. Enjuaga con agua y seca sin frotar. Evita el contacto directo con los ojos.',
            'featured' => true, 'image' => 'cleanser.webp', 'tag' => 'Esencial', 'step' => 1,
        ],
        [
            'slug' => 'serum-luz', 'name' => 'Sérum Luz', 'category' => 'tratamientos',
            'description' => 'Una textura liviana para acompañar tu rutina de luminosidad. Se integra fácilmente antes de la crema.',
            'price' => 98000, 'size' => '30 ml', 'skin' => ['seca', 'mixta', 'grasa'], 'concern' => 'luminosidad',
            'ingredients' => 'Derivado de vitamina C · glicerina · extracto de té verde.',
            'usage' => 'Aplica dos o tres gotas sobre la piel limpia y seca. Continúa con tu hidratante. Durante el día, termina con protección solar.',
            'featured' => true, 'image' => 'serum.webp', 'tag' => 'Favorito de la colección', 'step' => 2,
        ],
        [
            'slug' => 'crema-calma', 'name' => 'Crema Calma', 'category' => 'hidratacion',
            'description' => 'Hidratación envolvente en una crema de tacto sedoso. Un momento de pausa para la piel que se siente seca.',
            'price' => 86000, 'size' => '50 ml', 'skin' => ['seca', 'sensible'], 'concern' => 'hidratacion',
            'ingredients' => 'Escualano · ceramidas · glicerina.',
            'usage' => 'Extiende una capa fina sobre rostro y cuello después del sérum. Úsala por la mañana o por la noche según tu rutina.',
            'featured' => true, 'image' => 'cream.webp', 'tag' => 'Confort diario', 'step' => 3,
        ],
        [
            'slug' => 'fluido-solar', 'name' => 'Fluido Solar', 'category' => 'proteccion',
            'description' => 'El último paso de la rutina de día. Una propuesta de textura fluida y acabado ligero para el cuidado cotidiano.',
            'price' => 92000, 'size' => '50 ml', 'skin' => ['todas'], 'concern' => 'proteccion',
            'ingredients' => 'Fórmula de demostración: filtros solares · glicerina · vitamina E. Consulta siempre el etiquetado del producto real.',
            'usage' => 'En un producto real, sigue la cantidad, frecuencia de reaplicación y advertencias de su etiqueta. Complementa la protección con sombra y ropa adecuada.',
            'featured' => true, 'image' => 'sunscreen.webp', 'tag' => 'Rutina de día', 'step' => 4,
        ],
        [
            'slug' => 'gel-equilibrio', 'name' => 'Gel Equilibrio', 'category' => 'hidratacion',
            'description' => 'Hidratación ligera, de rápida absorción y acabado fresco. Pensada para quienes prefieren texturas en gel.',
            'price' => 78000, 'size' => '50 ml', 'skin' => ['mixta', 'grasa'], 'concern' => 'equilibrio',
            'ingredients' => 'Ácido hialurónico · glicerina · niacinamida.',
            'usage' => 'Distribuye una pequeña cantidad sobre el rostro limpio, después de tu tratamiento. Añade protección solar durante el día.',
            'featured' => false, 'image' => 'cream.webp', 'tag' => 'Textura ligera', 'step' => 3,
        ],
        [
            'slug' => 'serum-agua', 'name' => 'Sérum Agua', 'category' => 'tratamientos',
            'description' => 'Un gesto sencillo de hidratación. Sérum acuoso que deja una sensación fresca y se combina con tu crema diaria.',
            'price' => 89000, 'size' => '30 ml', 'skin' => ['todas'], 'concern' => 'hidratacion',
            'ingredients' => 'Ácido hialurónico · pantenol · glicerina.',
            'usage' => 'Aplica dos o tres gotas sobre el rostro limpio, ligeramente húmedo. Continúa con una crema o gel hidratante.',
            'featured' => false, 'image' => 'serum.webp', 'tag' => 'Hidratación', 'step' => 2,
        ],
        [
            'slug' => 'balsamo-suave', 'name' => 'Bálsamo Suave', 'category' => 'limpieza',
            'description' => 'Una textura que se funde al masajear para acompañar la limpieza de la noche y retirar maquillaje.',
            'price' => 74000, 'size' => '80 g', 'skin' => ['seca', 'sensible', 'mixta'], 'concern' => 'hidratacion',
            'ingredients' => 'Aceites emolientes · escualano · vitamina E.',
            'usage' => 'Masajea sobre el rostro seco. Añade un poco de agua para emulsionar y enjuaga. Si lo prefieres, continúa con un limpiador acuoso.',
            'featured' => false, 'image' => 'cream.webp', 'tag' => 'Ritual de noche', 'step' => 1,
        ],
        [
            'slug' => 'serum-balance', 'name' => 'Sérum Balance', 'category' => 'tratamientos',
            'description' => 'Un sérum ligero para una rutina de pocos pasos, con un acabado cómodo en pieles mixtas y grasas.',
            'price' => 94000, 'size' => '30 ml', 'skin' => ['mixta', 'grasa'], 'concern' => 'equilibrio',
            'ingredients' => 'Niacinamida · pantenol · glicerina.',
            'usage' => 'Aplica dos o tres gotas sobre la piel limpia y seca. Continúa con tu hidratante. Introduce cualquier producto nuevo de forma gradual.',
            'featured' => false, 'image' => 'serum.webp', 'tag' => 'Balance diario', 'step' => 2,
        ],
    ];
}

/** @return array|WP_Error Number created/skipped, or a recoverable import failure. */
function aluna_seed_catalog()
{
    // add_option is atomic at the DB level. The lock also prevents duplicate imports across nodes.
    $lock_key = 'aluna_seed_lock';
    $old_lock = (int) get_option($lock_key, 0);
    if ($old_lock && $old_lock < time() - 300) {
        delete_option($lock_key);
    }
    if (!add_option($lock_key, time(), '', false)) {
        return new WP_Error('aluna_seed_busy', 'Hay una carga del catálogo en curso. Espera un momento y vuelve a intentarlo.');
    }
    $created = 0;
    $skipped = 0;
    try {
        $terms = [];
        foreach (aluna_categories() as $slug => $label) {
            $term = term_exists($slug, 'aluna_category');
            if (!$term) {
                $term = wp_insert_term($label, 'aluna_category', ['slug' => $slug]);
            }
            if (is_wp_error($term)) {
                return $term;
            }
            $terms[$slug] = (int) (is_array($term) ? $term['term_id'] : $term);
        }
        foreach (aluna_seed_data() as $index => $product) {
            $existing = get_page_by_path($product['slug'], OBJECT, 'aluna_product');
            if ($existing) {
                $skipped++;
                continue;
            }
            $id = wp_insert_post([
                'post_type' => 'aluna_product', 'post_status' => 'draft',
                'post_title' => $product['name'], 'post_name' => $product['slug'],
                'post_excerpt' => $product['description'], 'post_content' => $product['description'],
                'menu_order' => $index + 1,
            ], true);
            if (is_wp_error($id)) {
                return $id;
            }
            foreach (aluna_meta_schema() as $key => $field) {
                update_post_meta($id, '_aluna_' . $key, $product[$key] ?? $field['default']);
            }
            $result = wp_set_object_terms($id, [$terms[$product['category']]], 'aluna_category');
            if (is_wp_error($result)) {
                wp_delete_post($id, true);
                return $result;
            }
            $published = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            if (is_wp_error($published)) {
                wp_delete_post($id, true);
                return $published;
            }
            $created++;
        }
        return ['created' => $created, 'skipped' => $skipped];
    } finally {
        delete_option($lock_key);
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('aluna seed', static function (): void {
        $result = aluna_seed_catalog();
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
            return;
        }
        WP_CLI::success(sprintf('Catálogo listo: %d nuevos; %d existentes conservados.', $result['created'], $result['skipped']));
    });
}
