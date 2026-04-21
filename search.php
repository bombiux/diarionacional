<?php
/**
 * Search Results Template
 */

use Timber\Timber;

$context = Timber::context();

// Get the search query
$search_query = get_search_query();
$context['search_query'] = $search_query;

// Get search results with pagination
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$args = [
    's' => $search_query,
    'posts_per_page' => 12,
    'post_type' => 'post',
    'paged' => $paged,
];
$context['posts'] = Timber::get_posts($args);

// Get total results count
global $wp_query;
$context['total_results'] = $wp_query->found_posts ?? 0;

Timber::render('search.twig', $context);
