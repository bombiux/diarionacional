<?php

namespace App\Setup;

/**
 * ArchivePagination Class
 * 
 * Handles custom posts_per_page for the first page of archives
 * while maintaining correct pagination logic and offsets.
 */
class ArchivePagination
{
    private $posts_first_page = 13;
    private $posts_per_page = 12;

    public function __construct()
    {
        add_action('pre_get_posts', [$this, 'adjustArchiveQuery']);
        add_filter('found_posts', [$this, 'adjustFoundPosts'], 1, 2);
    }

    /**
     * Adjusts the query for archives to handle different posts_per_page on page 1.
     */
    public function adjustArchiveQuery($query)
    {
        if (is_admin() || !$query->is_main_query() || !($query->is_archive() || $query->is_home())) {
            return;
        }

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        if ($paged == 1) {
            $query->set('posts_per_page', $this->posts_first_page);
        } else {
            // Calculate manual offset for pages > 1
            $offset = $this->posts_first_page + (($paged - 2) * $this->posts_per_page);
            $query->set('posts_per_page', $this->posts_per_page);
            $query->set('offset', $offset);
        }
    }

    /**
     * Adjusts the found_posts count so pagination calculation is correct.
     */
    public function adjustFoundPosts($found_posts, $query)
    {
        if (is_admin() || !$query->is_main_query() || !($query->is_archive() || $query->is_home())) {
            return $found_posts;
        }

        // Difference between first page and subsequent pages
        // We subtract the difference to "normalize" the count for page 2+
        return $found_posts - ($this->posts_first_page - $this->posts_per_page);
    }
}
