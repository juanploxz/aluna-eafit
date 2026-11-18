<?php defined('ABSPATH') || exit; ?>
<article class="product-card" data-product-card data-id="<?php echo esc_attr($p['id']); ?>" data-category="<?php echo esc_attr($p['category']); ?>" data-skin="<?php echo esc_attr(implode(' ', $p['skin'])); ?>">
    <div class="product-visual">
        <?php if (!empty($p['tag'])): ?><span class="product-tag"><?php echo esc_html($p['tag']); ?></span><?php endif; ?>
        <button type="button" class="favorite-button" data-favorite="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="Guardar <?php echo esc_attr($p['name']); ?> en favoritos"><?php aluna_icon('heart'); ?></button>
        <a href="<?php echo esc_url($p['url']); ?>" class="product-image-link" data-product-open="<?php echo esc_attr($p['id']); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url($p['imageUrl']); ?>" alt="" width="700" height="700" loading="lazy" decoding="async"></a>
        <a class="quick-view" href="<?php echo esc_url($p['url']); ?>" data-product-open="<?php echo esc_attr($p['id']); ?>" tabindex="-1" aria-hidden="true">Conocer producto <?php aluna_icon('arrow'); ?></a>
    </div>
    <div class="product-meta"><span><?php echo esc_html($p['categoryLabel']); ?></span><span><?php echo esc_html($p['size']); ?></span></div>
    <h3><a href="<?php echo esc_url($p['url']); ?>" data-product-open="<?php echo esc_attr($p['id']); ?>"><?php echo esc_html($p['name']); ?></a></h3>
    <div class="product-bottom"><p><?php echo esc_html(aluna_price((int)$p['price'])); ?> <span>COP</span></p><a class="round-link" href="<?php echo esc_url($p['url']); ?>" data-product-open="<?php echo esc_attr($p['id']); ?>" aria-label="Ver <?php echo esc_attr($p['name']); ?>"><?php aluna_icon('diagonal'); ?></a></div>
</article>
