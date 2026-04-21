<?php
/**
 * Template Name: Archivo de Historia
 *
 * Archive page showing 4 posts from each Historia subcategory.
 */

use Timber\Timber;

$context = Timber::context();

// Query posts from each subcategory (4 posts each)
$context['posts_historia'] = Timber::get_posts([
    'category_name' => 'historia',
    'posts_per_page' => 4,
]);

$context['posts_hechos_historicos'] = Timber::get_posts([
    'category_name' => 'hechos-historicos',
    'posts_per_page' => 4,
]);

$context['posts_ideario'] = Timber::get_posts([
    'category_name' => 'ideario',
    'posts_per_page' => 4,
]);

$context['posts_textos'] = Timber::get_posts([
    'category_name' => 'textos',
    'posts_per_page' => 4,
]);

$context['posts_undia'] = Timber::get_posts([
    'category_name' => 'un-dia-como-hoy-nacional',
    'posts_per_page' => 4,
]);

$context['posts_testimonios'] = Timber::get_posts([
    'category_name' => 'testimonios',
    'posts_per_page' => 4,
]);

$context['posts_dossier'] = Timber::get_posts([
    'category_name' => 'dossier',
    'posts_per_page' => 4,
]);

$context['posts_biografias'] = Timber::get_posts([
    'category_name' => 'biografias',
    'posts_per_page' => 4,
]);

Timber::render('archivo-historia.twig', $context);
