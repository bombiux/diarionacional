<?php
/**
 * Single Post Template
 * Displays a single post with the "Ethereal Archive" design
 */

use Timber\Timber;

$context = Timber::context();

// Get the current post
$post = Timber::get_post();

if ($post) {
    // Set up the post context
    $context['post'] = $post;
    
    // Get the post's category for related articles
    $categories = $post->terms(['taxonomy' => 'category']);
    $category_ids = wp_list_pluck($categories, 'term_id');
    
    // Get related articles from the same categories (excluding current post)
    $context['related_posts'] = Timber::get_posts([
        'post_type' => 'post',
        'posts_per_page' => 3,
        'post__not_in' => [$post->id],
        'tax_query' => [
            [
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $category_ids,
            ],
        ],
    ]);
    
    // If we don't have enough related posts, get recent posts
    $related_posts_arr = (is_array($context['related_posts']) ? $context['related_posts'] : $context['related_posts']->to_array());
    $related_count = count($related_posts_arr);
    
    if ($related_count < 3) {
        $additional_posts = Timber::get_posts([
            'post_type' => 'post',
            'posts_per_page' => 3 - $related_count,
            'post__not_in' => array_merge([$post->id], wp_list_pluck($related_posts_arr, 'id')),
        ]);
        
        $additional_posts_arr = (is_array($additional_posts) ? $additional_posts : $additional_posts->to_array());
        $context['related_posts'] = array_merge($related_posts_arr, $additional_posts_arr);
    } else {
        $context['related_posts'] = $related_posts_arr;
    }
    
    // Get reading time (estimated: 200 words per minute)
    $word_count = str_word_count(strip_tags($post->content));
    $context['reading_time'] = max(1, ceil($word_count / 200));

    // Get main category (excluding 'destacada')
    $main_category = null;
    $all_categories = $post->terms(['taxonomy' => 'category']);
    if (!empty($all_categories)) {
        foreach ($all_categories as $cat) {
            if ($cat->slug !== 'destacada' && $cat->slug !== 'destacadas') {
                $main_category = $cat;
                break;
            }
        }
        // Fallback to the first one if all are 'destacada'
        if (!$main_category) {
            $main_category = $all_categories[0];
        }
    }
    $context['main_category'] = $main_category;

    // Get formatted date for single post (Spanish support)
    $context['formatted_date'] = get_the_date('d \d\e F, Y', $post->ID);
    
    // Get author info
    $context['author'] = $post->author();
    
    Timber::render('single.twig', $context);
}
