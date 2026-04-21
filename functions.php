<?php

/**
 * Diario Nacional functions and definitions
 * Bootstrapper for modern WP setup
 */

if (! defined('ABSPATH')) {
    exit;
}

// 1. Composer Autoloader
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
} else {
    wp_die('Por favor ejecuta <code>composer install</code> en el directorio del tema.');
}

// 2. Load Environment Variables (Dotenv)
if (class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
    $dotenv->safeLoad();
}

// 3. Initialize App Setup Classes
new \App\Setup\Theme();
new \App\Setup\Security();
new \App\Setup\Vite();
new \App\Setup\Auth();
new \App\Setup\Seo();
new \App\Setup\CustomPostTypes();
new \App\Setup\CarbonFieldsSetup();
new \App\Setup\ArchivePagination();
new \App\Setup\Routes();

// 4. Initialize Services & API Endpoints
new \App\Services\SearchService();
new \App\Api\SearchEndpoint();

// 5. WP-CLI Custom Commands
if (defined('WP_CLI') && WP_CLI) {
    require_once get_template_directory() . '/app/Console/MigrateGalleryCommand.php';
    \WP_CLI::add_command('diarionacional migrate-galleries', \App\Console\MigrateGalleryCommand::class);
}