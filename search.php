<?php
/**
 * Search Results Template
 */

use Timber\Timber;

$context = Timber::context();

// Get the search query
$search_query = get_search_query();
$context['search_query'] = $search_query;

// Check if it's an HTMX request
$is_htmx = isset($_SERVER['HTTP_HX_REQUEST']);

// Adjust post count for live search
$posts_per_page = $is_htmx ? 6 : 12;

// Get search results with pagination
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$args = [
    's' => $search_query,
    'posts_per_page' => $posts_per_page,
    'post_type' => 'post',
    'paged' => $paged,
];
$context['posts'] = Timber::get_posts($args);

// Get total results count
global $wp_query;
$context['total_results'] = $wp_query->found_posts ?? 0;

if ($is_htmx) {
    Timber::render('partials/search-results.twig', $context);
} else {
    Timber::render('search.twig', $context);
}
