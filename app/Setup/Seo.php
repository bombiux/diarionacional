<?php

namespace App\Setup;

/**
 * Class Seo
 * Handles meta tags, Open Graph, Twitter Cards, and structured data (JSON-LD).
 */
class Seo
{
    public function __construct()
    {
        add_action('wp_head', [$this, 'addMetaTags'], 5);
        add_action('wp_head', [$this, 'addSchema'], 10);
    }

    public function addMetaTags()
    {
        global $post;
        
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        $url = home_url();
        $image = get_template_directory_uri() . '/assets/img/logo-dn.png';
        
        if (is_single() || is_page()) {
            $title = get_the_title() . ' - ' . get_bloginfo('name');
            $description = has_excerpt() ? get_the_excerpt() : wp_trim_words($post->post_content, 25);
            $url = get_permalink();
            
            if (has_post_thumbnail()) {
                $image = get_the_post_thumbnail_url($post->ID, 'large');
            }
        } elseif (is_category() || is_tag()) {
            $term = get_queried_object();
            $title = $term->name . ' - ' . get_bloginfo('name');
            $description = !empty($term->description)
                ? wp_trim_words($term->description, 25)
                : 'Artículos sobre ' . $term->name . ' en ' . get_bloginfo('name');
            $url = get_term_link($term);
        } elseif (is_search()) {
            $title = 'Resultados de búsqueda: ' . get_search_query() . ' - ' . get_bloginfo('name');
            $description = 'Resultados de búsqueda para "' . get_search_query() . '" en ' . get_bloginfo('name');
            $url = get_search_link();
        }
        
        echo "\n<!-- SEO Meta Tags -->\n";
        echo '<meta name="description" content="' . esc_attr(strip_tags($description)) . '">' . "\n";
        
        // Canonical URL (SEO-02)
        echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        
        // Meta robots (SEO-01)
        if (is_search() || is_404()) {
            echo '<meta name="robots" content="noindex, follow">' . "\n";
        } elseif (is_paged()) {
            echo '<meta name="robots" content="noindex, follow">' . "\n";
        } else {
            echo '<meta name="robots" content="index, follow, max-image-preview:large">' . "\n";
        }
        
        // Open Graph
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(strip_tags($description)) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:locale" content="es_NI">' . "\n";
        
        // Twitter
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(strip_tags($description)) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta name="twitter:site" content="@diarionacional">' . "\n";
    }

    public function addSchema()
    {
        global $post;
        $schemas = [];
        
        // WebSite schema with SearchAction (SEO-07) — always present
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        if (is_front_page() || is_home()) {
            // Organization schema
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'logo' => get_template_directory_uri() . '/assets/img/logo-dn.png',
                'sameAs' => [
                    'https://www.facebook.com/diarionacional',
                    'https://x.com/diarionacional',
                    'https://instagram.com/diarionacional',
                    'https://www.youtube.com/@diarionacional',
                    'https://www.tiktok.com/@diarionacional'
                ]
            ];
        } elseif (is_single()) {
            // NewsArticle schema (SEO-03: complete)
            $author_id = $post->post_author;
            $categories = get_the_category();
            $section = !empty($categories) ? $categories[0]->name : '';
            
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => get_permalink()
                ],
                'headline' => get_the_title(),
                'description' => has_excerpt() ? get_the_excerpt() : wp_trim_words($post->post_content, 25),
                'image' => [
                    has_post_thumbnail() ? get_the_post_thumbnail_url($post->ID, 'large') : get_template_directory_uri() . '/assets/img/logo-dn.png'
                ],
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
                'articleSection' => $section,
                'wordCount' => str_word_count(strip_tags($post->post_content)),
                'author' => [[
                    '@type' => 'Person',
                    'name' => get_the_author_meta('display_name', $author_id),
                    'url' => get_author_posts_url($author_id)
                ]],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => get_template_directory_uri() . '/assets/img/logo-dn.png'
                    ]
                ]
            ];
        } elseif (is_category() || is_tag()) {
            // CollectionPage schema (SEO-04)
            $term = get_queried_object();
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $term->name,
                'description' => !empty($term->description) ? $term->description : 'Artículos sobre ' . $term->name,
                'url' => get_term_link($term)
            ];
        }
        
        // BreadcrumbList schema (SEO-04) — for single posts and archives
        if (is_single() || is_category() || is_tag()) {
            $breadcrumbs = [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Inicio',
                    'item' => home_url()
                ]
            ];
            
            if (is_single()) {
                $categories = get_the_category();
                if (!empty($categories)) {
                    $breadcrumbs[] = [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $categories[0]->name,
                        'item' => get_category_link($categories[0]->term_id)
                    ];
                    $breadcrumbs[] = [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => get_the_title()
                    ];
                }
            } elseif (is_category() || is_tag()) {
                $term = get_queried_object();
                $breadcrumbs[] = [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $term->name
                ];
            }
            
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs
            ];
        }
        
        if (!empty($schemas)) {
            echo "\n<!-- JSON-LD Schema -->\n";
            foreach ($schemas as $schema) {
                echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }
        }
    }
}
