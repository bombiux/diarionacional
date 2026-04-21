<?php
/**
 * Script de migración de galerías ejecutable vía web o lsphp directo.
 */

// Cargar WordPress
$wp_load_path = dirname(__DIR__, 3) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    die("No se pudo encontrar wp-load.php en: $wp_load_path");
}

echo "<pre>\n";
echo "Iniciando migración de galerías...\n";

$query = new WP_Query([
    'category_name' => 'galeria',
    'posts_per_page' => -1,
    'post_type' => 'post',
    'post_status' => 'publish'
]);

$posts = $query->posts;
if (empty($posts)) {
    echo "No se encontraron posts en la categoría 'galeria'.\n";
    echo "</pre>";
    exit;
}

echo "Encontrados " . count($posts) . " posts para migrar.\n";

$count = 0;
foreach ($posts as $post) {
    echo "\nProcesando: {$post->post_title}...\n";

    // 1. Extract images
    $image_ids = [];
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
    if (preg_match('/\[gallery.*ids=.(.*).\]/', $post->post_content, $matches) ) {
        if (isset($matches[1])) {
            $ids = explode(',', str_replace('"', '', $matches[1]));
            $image_ids = array_merge($image_ids, array_map('intval', $ids));
        }
    }
    if (preg_match_all('/class="[^"]*wp-image-(\d+)[^"]*"/', $post->post_content, $img_matches)) {
        if (!empty($img_matches[1])) {
            $image_ids = array_merge($image_ids, array_map('intval', $img_matches[1]));
        }
    }
    if (empty($image_ids)) {
        $attached_media = get_attached_media('image', $post->ID);
        foreach ($attached_media as $media) {
            $image_ids[] = $media->ID;
        }
    }
    $image_ids = array_unique($image_ids);

    // 2. Extract content
    $content = $post->post_content;
    $content = preg_replace('/\[gallery.*\]/', '', $content);
    $content = preg_replace('/<!-- wp:gallery.*<!-- \/wp:gallery -->/s', '', $content);
    $content = preg_replace('/<!-- wp:image.*<!-- \/wp:image -->/s', '', $content);
    $content = preg_replace('/<img[^>]+>/i', '', $content);
    $content = trim(strip_tags($content, '<p><b><i><strong><em><a><br><ul><ol><li>'));

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
        echo "Error al crear post para: {$post->post_title}\n";
        continue;
    }

    // 4. Set Carbon Fields
    if (function_exists('carbon_set_post_meta')) {
        if (!empty($image_ids)) {
            carbon_set_post_meta($new_post_id, 'crb_media_gallery', $image_ids);
        }
        if (!empty($content)) {
            carbon_set_post_meta($new_post_id, 'crb_gallery_description', $content);
        }
        echo "   - Adjuntadas " . count($image_ids) . " imágenes a Carbon Fields.\n";
    } else {
        update_post_meta($new_post_id, '_crb_media_gallery', $image_ids);
        update_post_meta($new_post_id, '_crb_gallery_description', $content);
        echo "   - (Fallback) Adjuntadas " . count($image_ids) . " imágenes.\n";
    }

    // 5. Trash original post
    wp_trash_post($post->ID);
    
    $count++;
    echo "   - Migrado correctamente ($count/" . count($posts) . ").\n";
}

echo "\n¡Migración completada! Se migraron $count galerías.\n";
echo "</pre>\n";
