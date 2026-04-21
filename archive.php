<?php
/**
 * Archive Template
 *
 * Handles category, tag, date and other archive pages.
 * Prepares context with Timber and renders views/archive.twig.
 */

use Timber\Timber;

$context = Timber::context();

// Current queried term (category, tag, etc.)
$context['category'] = get_queried_object();

// Get archive posts using the main query (adjusted by ArchivePagination hooks)
$context['posts'] = Timber::get_posts();

global $wp_query;
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Get total posts count for the archive (real count)
$context['total_posts'] = $wp_query->found_posts ?? 0;

// Featured post: first post on page 1 only
if ($paged === 1 && !empty($context['posts'])) {
    $posts_array = $context['posts']->to_array();
    $context['featured_post'] = $posts_array[0] ?? null;
} else {
    $context['featured_post'] = null;
}

// Archive title and description
$context['archive_title'] = get_the_archive_title();
$context['archive_description'] = get_the_archive_description();

Timber::render('archive.twig', $context);
