<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="contenido" class="wrap text-page section-space">
<?php if (have_posts()): ?>
<?php if (is_search()): ?><p class="eyebrow">RESULTADOS DE BÚSQUEDA</p><h1><?php echo esc_html(get_search_query()); ?></h1><?php elseif (is_archive()): ?><h1><?php echo esc_html(wp_strip_all_tags(get_the_archive_title())); ?></h1><?php elseif (!is_singular()): ?><h1>Explora ALUNA.</h1><?php endif; ?>
<?php while (have_posts()): the_post(); ?><article><?php if (is_singular()): ?><h1><?php echo esc_html(get_the_title()); ?></h1><?php the_content(); else: ?><h2><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a></h2><?php the_excerpt(); endif; ?></article><?php endwhile; the_posts_pagination(); ?>
<?php else: ?><h1>No encontramos resultados.</h1><p>Prueba buscando en la colección.</p><?php endif; ?>
<a class="button primary" href="<?php echo esc_url(home_url('/#coleccion')); ?>">Explorar la colección <?php aluna_icon('arrow'); ?></a>
</main><?php get_footer(); ?>
