<?php

/**
 * Import content from data/export.json into local WordPress.
 *
 * This script runs inside the Docker container with full WP context.
 * Usage: docker exec <container> php scripts/import-content.php
 */

// 1. Load WordPress
// __DIR__ = .../DBarricada/scripts
// dirname(__DIR__, 4) = /var/www/html/
require_once dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Load export data
$exportFile = __DIR__ . '/../data/export.json';
if (!file_exists($exportFile)) {
    echo "❌ No se encontró data/export.json. Ejecuta primero: npm run export:content\n";
    exit(1);
}

$exportData = json_decode(file_get_contents($exportFile), true);
if (!$exportData) {
    echo "❌ Error al leer data/export.json\n";
    exit(1);
}

// ANSI color codes for output
function color($text, $code)
{
    return "\033[{$code}m{$text}\033[0m";
}
function success($text)
{
    return color($text, '32'); // green
}
function warning($text)
{
    return color($text, '33'); // yellow
}
function info($text)
{
    return color($text, '36'); // cyan
}
function error($text)
{
    return color($text, '31'); // red
}

// Counters
$stats = [
    'categories_created' => 0,
    'categories_existing' => 0,
    'authors_created' => 0,
    'authors_existing' => 0,
    'posts_imported' => 0,
    'posts_skipped' => 0,
    'images_downloaded' => 0,
    'images_fallback' => 0,
];

echo "🚀 Importando contenido desde {$exportData['source']}\n";
echo "📅 Exportado: {$exportData['exported_at']}\n\n";

// 2. Copy fallback.jpg if needed
$uploads = wp_upload_dir();
$fallbackDest = $uploads['basedir'] . '/fallback.jpg';
$fallbackSource = __DIR__ . '/../data/fallback.jpg';

if (!file_exists($fallbackDest)) {
    if (file_exists($fallbackSource)) {
        copy($fallbackSource, $fallbackDest);
        echo "🖼️  Fallback image copied to uploads directory\n";
    } else {
        echo warning("⚠️  No se encontró data/fallback.jpg. Los posts antiguos no tendrán imagen.\n");
    }
} else {
    echo "🖼️  Fallback image already exists\n";
}

// 3. Create categories
echo "\n📂 Processing categories...\n";
$categorySourceToLocalMap = [];

foreach ($exportData['categories'] as $category) {
    $existing = get_term_by('slug', $category['slug'], 'category');

    if ($existing) {
        $categorySourceToLocalMap[$category['source_id']] = $existing->term_id;
        $stats['categories_existing']++;
        echo "  ✓ {$category['name']} (exists)\n";
        continue;
    }

    $parentId = 0;
    if ($category['parent'] > 0 && isset($categorySourceToLocalMap[$category['parent']])) {
        $parentId = $categorySourceToLocalMap[$category['parent']];
    }

    $result = wp_insert_term(
        $category['name'],
        'category',
        [
            'slug' => $category['slug'],
            'parent' => $parentId,
        ]
    );

    if (!is_wp_error($result)) {
        $categorySourceToLocalMap[$category['source_id']] = $result['term_id'];
        $stats['categories_created']++;
        echo "  + {$category['name']}\n";
    } else {
        echo error("  ✗ Error creating {$category['name']}: {$result->get_error_message()}\n");
    }
}

echo success("✅ Categories: " . ($stats['categories_created'] + $stats['categories_existing']) .
    " (created: {$stats['categories_created']}, existing: {$stats['categories_existing']})\n");

// 4. Create authors
echo "\n👤 Processing authors...\n";
$authorSourceToLocalMap = [];

foreach ($exportData['authors'] as $author) {
    $existing = get_user_by('slug', $author['slug']);

    if ($existing) {
        $authorSourceToLocalMap[$author['source_id']] = $existing->ID;
        $stats['authors_existing']++;
        echo "  ✓ {$author['name']} (exists)\n";
        continue;
    }

    $userId = wp_insert_user([
        'user_login' => $author['slug'],
        'display_name' => $author['name'],
        'user_nicename' => $author['slug'],
        'nickname' => $author['name'],
        'role' => 'author',
        'user_pass' => wp_generate_password(24),
    ]);

    if (!is_wp_error($userId)) {
        $authorSourceToLocalMap[$author['source_id']] = $userId;
        $stats['authors_created']++;
        echo "  + {$author['name']}\n";
    } else {
        echo error("  ✗ Error creating {$author['name']}: {$userId->get_error_message()}\n");
    }
}

echo success("✅ Authors: " . ($stats['authors_created'] + $stats['authors_existing']) .
    " (created: {$stats['authors_created']}, existing: {$stats['authors_existing']})\n");

// 5. Import posts
echo "\n📝 Processing posts...\n";

foreach ($exportData['posts'] as $postData) {
    // Check for duplicate by slug
    $existingPost = get_page_by_path($postData['slug'], OBJECT, 'post');

    if ($existingPost) {
        $stats['posts_skipped']++;
        echo "  ~ {$postData['title']} (exists)\n";
        continue;
    }

    // Determine author ID
    $authorId = $authorSourceToLocalMap[$postData['author_id']] ?? 1; // fallback to admin (ID=1)

    // Prepare post data
    $postArgs = [
        'post_title' => wp_strip_all_tags($postData['title']),
        'post_content' => $postData['content'],
        'post_excerpt' => wp_strip_all_tags($postData['excerpt']),
        'post_name' => $postData['slug'],
        'post_date' => get_date_from_gmt($postData['date']),
        'post_status' => 'publish',
        'post_author' => $authorId,
        'post_type' => 'post',
    ];

    $postId = wp_insert_post($postArgs, true);

    if (is_wp_error($postId)) {
        echo error("  ✗ Error importing {$postData['title']}: {$postId->get_error_message()}\n");
        continue;
    }

    $stats['posts_imported']++;
    echo "  + {$postData['title']}\n";

    // Assign categories
    $categoryIds = [];
    foreach ($postData['category_slugs'] as $slug) {
        $term = get_term_by('slug', $slug, 'category');
        if ($term) {
            $categoryIds[] = $term->term_id;
        }
    }

    if (!empty($categoryIds)) {
        wp_set_post_categories($postId, $categoryIds);
    }

    // Assign tags
    if (!empty($postData['tag_names'])) {
        wp_set_post_tags($postId, $postData['tag_names']);
    }

    // Handle featured image
    if (!empty($postData['featured_image_url']) && file_exists($fallbackDest)) {
        if ($postData['image_strategy'] === 'download') {
            // Download real image from production
            $title = sanitize_title($postData['title']);
            $attachmentId = media_sideload_image($postData['featured_image_url'], $postId, $title, 'id');

            if (!is_wp_error($attachmentId)) {
                set_post_thumbnail($postId, $attachmentId);
                $stats['images_downloaded']++;
            } else {
                echo warning("    ⚠️  Failed to download image for {$postData['title']}\n");
            }
        } else {
            // Create attachment pointing to fallback.jpg
            $attachmentId = wp_insert_attachment([
                'post_mime_type' => 'image/jpeg',
                'post_title' => 'Placeholder - ' . wp_strip_all_tags($postData['title']),
                'post_status' => 'inherit',
                'guid' => $uploads['baseurl'] . '/fallback.jpg',
            ], $fallbackDest, $postId);

            if (!is_wp_error($attachmentId)) {
                // Generate attachment metadata
                $metadata = wp_generate_attachment_metadata($attachmentId, $fallbackDest);
                wp_update_attachment_metadata($attachmentId, $metadata);
                set_post_thumbnail($postId, $attachmentId);
                $stats['images_fallback']++;
            }
        }
    }
}

// 6. Final report
echo "\n" . str_repeat('=', 50) . "\n";
echo success("✅ Import complete!\n");
echo "📊 Stats:\n";
echo "   Categories: " . ($stats['categories_created'] + $stats['categories_existing']) .
    " (created: {$stats['categories_created']}, existing: {$stats['categories_existing']})\n";
echo "   Authors: " . ($stats['authors_created'] + $stats['authors_existing']) .
    " (created: {$stats['authors_created']}, existing: {$stats['authors_existing']})\n";
echo "   Posts: " . ($stats['posts_imported'] + $stats['posts_skipped']) .
    " (imported: {$stats['posts_imported']}, skipped: {$stats['posts_skipped']})\n";
echo "   📸 Images downloaded: {$stats['images_downloaded']} (last 7 days)\n";
echo "   🖼️  Images fallback: {$stats['images_fallback']}\n";
echo str_repeat('=', 50) . "\n";
