<?php
/**
 * Author Template
 *
 * Handles the author profile page and their published posts.
 * Prepares context with Timber and renders views/author.twig.
 */

use Timber\Timber;
use Timber\User;

$context          = Timber::context();
$author           = Timber::get_user(get_query_var('author'));
$context['author'] = $author;

// Get author's posts
$context['posts'] = Timber::get_posts([
    'author' => get_query_var('author'),
    'paged'  => get_query_var('paged') ?: 1
]);

global $wp_query;
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Get total posts count for the author
$context['total_posts'] = $wp_query->found_posts ?? 0;

// Featured post: first post on page 1 only
if ($paged === 1 && !empty($context['posts'])) {
    $posts_array = $context['posts']->to_array();
    $context['featured_post'] = $posts_array[0] ?? null;
} else {
    $context['featured_post'] = null;
}

Timber::render('author.twig', $context);
