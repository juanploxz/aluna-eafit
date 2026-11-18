<?php
/** Native WordPress editor controls. */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes_aluna_product', static function (): void {
    add_meta_box('aluna-product-details', 'Ficha del producto', 'aluna_product_meta_box', 'aluna_product', 'normal', 'high');
});

function aluna_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('aluna_save_product', 'aluna_product_nonce');
    $values = [];
    foreach (aluna_meta_schema() as $key => $field) {
        $raw = get_post_meta($post->ID, '_aluna_' . $key, true);
        $values[$key] = call_user_func($field['sanitize_callback'], $raw !== '' ? $raw : $field['default']);
    }
    ?>
    <p>La descripción se toma del extracto; si está vacío, se utiliza el contenido como texto. La imagen destacada reemplaza la fotografía del catálogo. Los importes son precios de referencia en pesos colombianos; este sitio no procesa pagos.</p>
    <table class="form-table" role="presentation">
        <tr><th><label for="aluna-price">Precio de referencia (COP)</label></th><td><input id="aluna-price" name="aluna[price]" type="number" min="0" max="100000000" step="1" value="<?php echo esc_attr($values['price']); ?>" required></td></tr>
        <tr><th><label for="aluna-size">Presentación</label></th><td><input class="regular-text" id="aluna-size" name="aluna[size]" maxlength="80" value="<?php echo esc_attr($values['size']); ?>" placeholder="50 ml"></td></tr>
        <tr><th>Tipo de piel</th><td><fieldset><legend class="screen-reader-text">Tipos de piel compatibles</legend>
            <?php foreach (aluna_skin_types() as $slug => $label) : ?>
                <label style="display:inline-block;margin:0 16px 8px 0"><input type="checkbox" name="aluna[skin][]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, (array) $values['skin'], true)); ?>> <?php echo esc_html($label); ?></label>
            <?php endforeach; ?>
        </fieldset></td></tr>
        <tr><th><label for="aluna-concern">Objetivo de la rutina</label></th><td><select id="aluna-concern" name="aluna[concern]">
            <?php foreach (['hidratacion' => 'Hidratación', 'equilibrio' => 'Equilibrio', 'luminosidad' => 'Luminosidad', 'proteccion' => 'Protección'] as $slug => $label) : ?>
                <option value="<?php echo esc_attr($slug); ?>" <?php selected($values['concern'], $slug); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select></td></tr>
        <tr><th><label for="aluna-ingredients">Ingredientes destacados</label></th><td><textarea class="large-text" rows="3" id="aluna-ingredients" name="aluna[ingredients]" maxlength="3000"><?php echo esc_textarea($values['ingredients']); ?></textarea><p class="description">Resumen editorial; no sustituye la lista INCI del empaque.</p></td></tr>
        <tr><th><label for="aluna-usage">Modo de uso</label></th><td><textarea class="large-text" rows="4" id="aluna-usage" name="aluna[usage]" maxlength="3000"><?php echo esc_textarea($values['usage']); ?></textarea></td></tr>
        <tr><th><label for="aluna-step">Paso de la rutina</label></th><td><select id="aluna-step" name="aluna[step]">
            <?php foreach ([1 => '1 · Limpiar', 2 => '2 · Tratar', 3 => '3 · Hidratar', 4 => '4 · Proteger'] as $step => $label) : ?>
                <option value="<?php echo esc_attr($step); ?>" <?php selected((int) $values['step'], $step); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select></td></tr>
        <tr><th><label for="aluna-image">Imagen de la colección</label></th><td><select id="aluna-image" name="aluna[image]">
            <?php foreach (['cleanser.webp' => 'Limpiador', 'serum.webp' => 'Sérum', 'cream.webp' => 'Crema', 'sunscreen.webp' => 'Protector'] as $file => $label) : ?>
                <option value="<?php echo esc_attr($file); ?>" <?php selected($values['image'], $file); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select><p class="description">Se usa cuando no hay una imagen destacada.</p></td></tr>
        <tr><th><label for="aluna-tag">Etiqueta breve</label></th><td><input class="regular-text" id="aluna-tag" name="aluna[tag]" maxlength="40" value="<?php echo esc_attr($values['tag']); ?>" placeholder="Esencial"></td></tr>
        <tr><th>Selección destacada</th><td><label><input type="checkbox" name="aluna[featured]" value="1" <?php checked((bool) $values['featured']); ?>> Mostrar en los destacados de la portada</label></td></tr>
    </table>
    <?php
}

add_action('save_post_aluna_product', static function (int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $nonce = isset($_POST['aluna_product_nonce']) && is_string($_POST['aluna_product_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['aluna_product_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'aluna_save_product') || !isset($_POST['aluna']) || !is_array($_POST['aluna'])) {
        return;
    }
    $values = wp_unslash($_POST['aluna']);
    foreach (aluna_meta_schema() as $key => $field) {
        $raw = $values[$key] ?? ($key === 'featured' ? false : $field['default']);
        if ($field['type'] !== 'array' && is_array($raw)) {
            continue;
        }
        $clean = call_user_func($field['sanitize_callback'], $raw);
        if ($key === 'price') {
            $clean = min(100000000, $clean);
        }
        update_post_meta($post_id, '_aluna_' . $key, $clean);
    }
});

add_action('admin_menu', static function (): void {
    add_submenu_page('edit.php?post_type=aluna_product', 'Preparar catálogo', 'Preparar catálogo', 'manage_options', 'aluna-setup', 'aluna_setup_page');
});

function aluna_setup_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Catálogo ALUNA</h1>
        <p>Carga ocho productos de muestra editables para preparar la presentación. La operación agrega únicamente productos que no existen y conserva las ediciones anteriores.</p>
        <p><strong>Contenido académico:</strong> ALUNA es una marca ficticia de XYZ S.A. Los productos, precios e ingredientes son ejemplos para el proyecto y no constituyen una oferta comercial.</p>
        <?php if (isset($_GET['aluna_seeded'])) : ?>
            <div class="notice notice-success"><p>Catálogo preparado. Productos nuevos: <?php echo esc_html((string) absint($_GET['aluna_seeded'])); ?>.</p></div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="aluna_seed_catalog">
            <?php wp_nonce_field('aluna_seed_catalog'); ?>
            <?php submit_button('Cargar catálogo de muestra'); ?>
        </form>
        <h2>Edición del catálogo</h2>
        <p>En <strong>Productos ALUNA</strong> puedes cambiar el nombre, el extracto, la categoría, el precio y la ficha de cada producto. La imagen destacada sustituye la imagen de colección. Usa el campo Orden para decidir su posición.</p>
        <h2>Diagnóstico de la instancia</h2>
        <?php $identity = aluna_server_identity(); ?>
        <p>IPv4 privada detectada: <code><?php echo esc_html($identity['ip'] ?? 'No disponible en este entorno'); ?></code>.</p>
        <p>La IP se calcula por instancia. Si el servidor no expone su dirección local a PHP, configura <code>ALUNA_NODE_IP</code> en cada instancia con su IPv4 privada real. No guardes este valor como una opción compartida de WordPress.</p>
    </div>
    <?php
}

add_action('admin_post_aluna_seed_catalog', static function (): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_die(esc_html__('Usa el formulario para preparar el catálogo.', 'aluna'), '', ['response' => 405]);
    }
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permiso para preparar el catálogo.', 'aluna'), '', ['response' => 403]);
    }
    check_admin_referer('aluna_seed_catalog');
    $result = aluna_seed_catalog();
    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()), 'Catálogo ALUNA', ['response' => 500, 'back_link' => true]);
    }
    wp_safe_redirect(add_query_arg(['post_type' => 'aluna_product', 'page' => 'aluna-setup', 'aluna_seeded' => $result['created']], admin_url('edit.php')));
    exit;
});
