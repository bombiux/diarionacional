<?php
/**
 * Main Template File
 */

use Timber\Timber;

$context = Timber::context();

// 1. Hero Post (Featured)
$context['hero_post'] = Timber::get_post([
    'category_name' => 'destacadas',
    'posts_per_page' => 1
]);

// 2. Nacionales (2 posts)
$context['posts_nacionales'] = Timber::get_posts([
    'category_name' => 'nacionales',
    'posts_per_page' => 2,
    'post__not_in' => $context['hero_post'] ? [$context['hero_post']->id] : []
]);

// 3. Internacionales (2 posts)
$context['posts_internacionales'] = Timber::get_posts([
    'category_name' => 'internacionales',
    'posts_per_page' => 2,
    'post__not_in' => $context['hero_post'] ? [$context['hero_post']->id] : []
]);

// 3.5 Viral (3 posts for trending widget)
$context['posts_viral'] = Timber::get_posts([
    'category_name' => 'viral',
    'posts_per_page' => 3,
]);

// 4. Ambiente (2 posts)
$context['posts_ambiente'] = Timber::get_posts([
    'category_name' => 'ambiente',
    'posts_per_page' => 2
]);

// 5. Deportes (2 posts)
$context['posts_deportes'] = Timber::get_posts([
    'category_name' => 'deportes',
    'posts_per_page' => 2
]);

// 6. Salud (2 posts)
$context['posts_salud'] = Timber::get_posts([
    'category_name' => 'salud',
    'posts_per_page' => 2
]);

// 7. Agricultura (2 posts)
$context['posts_agricultura'] = Timber::get_posts([
    'category_name' => 'agricultura',
    'posts_per_page' => 2
]);

// 8. Tecnología (2 posts)
$context['posts_tecnologia'] = Timber::get_posts([
    'category_name' => 'tecnologia',
    'posts_per_page' => 2
]);

// 9. Farándula (2 posts)
$context['posts_farandula'] = Timber::get_posts([
    'category_name' => 'farandula',
    'posts_per_page' => 2
]);

// 10. Análisis (1 post)
$context['latest_analisis'] = Timber::get_post([
    'category_name' => 'analisis',
    'posts_per_page' => 1
]);

// 7. Galerías (4 posts for widget)
$galleries = Timber::get_posts([
    'post_type' => 'galeria',
    'posts_per_page' => 4
]);

$latest_galleries = [];
foreach ($galleries as $gallery) {
    if (function_exists('carbon_get_post_meta')) {
        $gallery_ids = carbon_get_post_meta($gallery->ID, 'crb_media_gallery');
    } else {
        $gallery_ids = get_post_meta($gallery->ID, '_crb_media_gallery', true);
    }
    
    if (!empty($gallery_ids) && is_array($gallery_ids) && isset($gallery_ids[0])) {
        $gallery->cover_image = Timber::get_image($gallery_ids[0]);
    } else {
        // Fallback or featured image if user somehow uploaded one manually
        $gallery->cover_image = $gallery->thumbnail; 
    }
    $latest_galleries[] = $gallery;
}
$context['latest_galleries'] = $latest_galleries;

// Load general posts if hero was not found (fallback)
if (!$context['hero_post']) {
    $context['hero_post'] = Timber::get_post();
}

Timber::render('index.twig', $context);
