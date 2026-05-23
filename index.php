<?php
/**
 * Main Template File
 */

use Timber\Timber;
use App\Services\YouTubeService;

$context = Timber::context();

// 1. Hero Post (Featured)
$context['hero_post'] = Timber::get_post([
    'category_name' => 'destacadas',
    'posts_per_page' => 1
]);

// 2. Nacionales (6 posts)
$context['posts_nacionales'] = Timber::get_posts([
    'category_name' => 'nacionales',
    'posts_per_page' => 6,
    'post__not_in' => $context['hero_post'] ? [$context['hero_post']->id] : []
]);

// 3.5 Viral (3 posts for trending widget)
$context['posts_viral'] = Timber::get_posts([
    'category_name' => 'viral',
    'posts_per_page' => 3,
]);

// 9. Farándula (2 posts)
$context['posts_farandula'] = Timber::get_posts([
    'category_name' => 'farandula',
    'posts_per_page' => 2
]);

// 9.1 Tecnología (2 posts)
$context['posts_tecnologia'] = Timber::get_posts([
    'category_name' => 'tecnologia',
    'posts_per_page' => 2
]);

// 9.5 Turismo (1 post for specialized hero)
$context['posts_turismo'] = Timber::get_posts([
    'category_name' => 'turismo',
    'posts_per_page' => 1
]);

// 10. Análisis (1 post)
$context['latest_analisis'] = Timber::get_post([
    'category_name' => 'analisis',
    'posts_per_page' => 1
]);

// 11. YouTube Videos (4 videos dinámicos)
$context['youtube_videos'] = YouTubeService::getRecentVideos(4);

// Load general posts if hero was not found (fallback)
if (!$context['hero_post']) {
    $context['hero_post'] = Timber::get_post();
}

Timber::render('index.twig', $context);

