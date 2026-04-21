<?php
/**
 * Single Galería Template
 *
 * Displays a single gallery post with interactive lightbox.
 */

use Timber\Timber;

$context = Timber::context();
$post = Timber::get_post();

$context['post'] = $post;

// Obtain data from Carbon Fields
$gallery_ids = [];
$gallery_description = '';

if (function_exists('carbon_get_post_meta')) {
    $gallery_ids = carbon_get_post_meta($post->ID, 'crb_media_gallery');
    $gallery_description = carbon_get_post_meta($post->ID, 'crb_gallery_description');
} else {
    $gallery_ids = get_post_meta($post->ID, '_crb_media_gallery', true);
    $gallery_description = get_post_meta($post->ID, '_crb_gallery_description', true);
}

// Handle "Download All" request
if (isset($_GET['download_all']) && $_GET['download_all'] === 'true' && !empty($gallery_ids)) {
    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'galeria_');
    
    if ($zip->open($tmp_file, ZipArchive::CREATE) === TRUE) {
        foreach ($gallery_ids as $id) {
            $filepath = get_attached_file($id);
            if ($filepath && file_exists($filepath)) {
                $zip->addFile($filepath, basename($filepath));
            }
        }
        $zip->close();
        
        $zip_filename = sanitize_title($post->title) . '-galeria.zip';
        
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($tmp_file));
        readfile($tmp_file);
        unlink($tmp_file);
        exit;
    }
}

// Convert IDs to Timber\Image objects
$images = [];
if (!empty($gallery_ids) && is_array($gallery_ids)) {
    foreach ($gallery_ids as $id) {
        $images[] = Timber::get_image($id);
    }
}
$context['gallery_images'] = $images;
$context['gallery_description'] = $gallery_description;

Timber::render('single-galeria.twig', $context);
