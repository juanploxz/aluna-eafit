<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html lang="es-CO">
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="announcement"><span>Un momento para ti. Todos los días.</span><span>Hecho para tu ritmo <span aria-hidden="true">✳</span></span></div>
<header class="site-header">
    <a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="ALUNA, inicio">aluna<span class="logo-dot" aria-hidden="true">✳</span></a>
    <nav class="main-nav" id="main-nav" aria-label="Principal">
        <a href="<?php echo esc_url(home_url('/#coleccion')); ?>">La colección</a>
        <a href="<?php echo esc_url(home_url('/#ritual')); ?>">Tu ritual</a>
        <a href="<?php echo esc_url(home_url('/#nosotros')); ?>">Nuestra esencia</a>
        <a href="<?php echo esc_url(home_url('/#sedes')); ?>">Encuéntranos</a>
    </nav>
    <div class="header-actions">
        <button class="icon-button" type="button" data-search-open aria-label="Buscar productos"><?php aluna_icon('search'); ?></button>
        <button class="icon-button favorite-header" type="button" data-favorites-open aria-label="Ver favoritos"><?php aluna_icon('heart'); ?><span data-favorites-count hidden>0</span></button>
        <button class="icon-button menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav"><?php aluna_icon('menu'); ?></button>
    </div>
</header>
