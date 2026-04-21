<?php
namespace App\Console;

use WP_CLI;
use WP_Query;

class MigrateGalleryCommand {
    public function __invoke($args, $assoc_args) {
        WP_CLI::log( 'Starting gallery migration...' );

        $query = new WP_Query([
            'category_name' => 'galeria',
            'posts_per_page' => -1,
            'post_type' => 'post',
            'post_status' => 'publish'
        ]);

        $posts = $query->posts;
        if (empty($posts)) {
            WP_CLI::success('No posts found in the "galeria" category.');
            return;
        }

        WP_CLI::log( 'Found ' . count($posts) . ' posts to migrate.' );

        $count = 0;
        foreach ($posts as $post) {
            WP_CLI::log("Processing: {$post->post_title}...");

            // 1. Extract images
            $image_ids = $this->extractImages($post);

            // 2. Extract paragraph/content (strip img tags and gallery shortcodes)
            $content = $this->cleanContent($post->post_content);

            // 3. Create new CPT
            $new_post_id = wp_insert_post([
                'post_title' => $post->post_title,
                'post_content' => '',
                'post_excerpt' => $post->post_excerpt,
                'post_status' => 'publish',
                'post_type' => 'galeria',
                'post_author' => $post->post_author,
                'post_date' => $post->post_date,
            ]);

            if (is_wp_error($new_post_id)) {
                WP_CLI::warning("Failed to create post for: {$post->post_title}");
                continue;
            }

            // 4. Set Carbon Fields (Images & Description)
            if (function_exists('carbon_set_post_meta')) {
                if (!empty($image_ids)) {
                    carbon_set_post_meta($new_post_id, 'crb_media_gallery', $image_ids);
                }
                if (!empty($content)) {
                    carbon_set_post_meta($new_post_id, 'crb_gallery_description', $content);
                }
                WP_CLI::log("   - Attached " . count($image_ids) . " images and description to Carbon Fields.");
            } else {
                update_post_meta($new_post_id, '_crb_media_gallery', $image_ids);
                update_post_meta($new_post_id, '_crb_gallery_description', $content);
            }

            // 5. Trash original post
            wp_trash_post($post->ID);
            
            $count++;
            WP_CLI::success("Migrated successfully ($count/" . count($posts) . ").");
        }

        WP_CLI::success( 'Migration complete! Migrated ' . $count . ' galleries.' );
    }

    private function extractImages($post) {
        $image_ids = [];

        // 1. Parse Gutenberg Gallery blocks
        if ( function_exists('parse_blocks') && has_blocks( $post->post_content ) ) {
            $blocks = parse_blocks( $post->post_content );
            foreach ( $blocks as $block ) {
                if ( 'core/gallery' === $block['blockName'] && !empty($block['attrs']['ids']) ) {
                    $image_ids = array_merge($image_ids, $block['attrs']['ids']);
                }
                if ( 'core/image' === $block['blockName'] && !empty($block['attrs']['id']) ) {
                    $image_ids[] = $block['attrs']['id'];
                }
            }
        }

        // 2. Parse Native shortcode [gallery ids="1,2,3"]
        if (preg_match('/\[gallery.*ids=.(.*).\]/', $post->post_content, $matches) ) {
            if (isset($matches[1])) {
                $ids = explode(',', str_replace('"', '', $matches[1]));
                $image_ids = array_merge($image_ids, array_map('intval', $ids));
            }
        }
        
        // 3. Parse raw img tags class "wp-image-123"
        if (preg_match_all('/class="[^"]*wp-image-(\d+)[^"]*"/', $post->post_content, $img_matches)) {
            if (!empty($img_matches[1])) {
                $image_ids = array_merge($image_ids, array_map('intval', $img_matches[1]));
            }
        }

        // 4. Try getting attached media directly first if none of the above worked
        if (empty($image_ids)) {
            $attached_media = get_attached_media('image', $post->ID);
            foreach ($attached_media as $media) {
                $image_ids[] = $media->ID;
            }
        }

        return array_unique($image_ids);
    }

    private function cleanContent($content) {
        // Remove gallery shortcode
        $content = preg_replace('/\[gallery.*\]/', '', $content);
        // Remove Gutenberg gallery blocks
        $content = preg_replace('/<!-- wp:gallery.*<!-- \/wp:gallery -->/s', '', $content);
        // Remove Gutenberg image blocks
        $content = preg_replace('/<!-- wp:image.*<!-- \/wp:image -->/s', '', $content);
        // Remove stray <img> tags
        $content = preg_replace('/<img[^>]+>/i', '', $content);
        
        return trim(strip_tags($content, '<p><b><i><strong><em><a><br><ul><ol><li>'));
    }
}
