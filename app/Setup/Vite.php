<?php

namespace App\Setup;

class Vite
{
    private string $distUri;
    private string $distPath;
    private array $manifest = [];

    public function __construct()
    {
        $this->distUri = get_template_directory_uri() . '/dist';
        $this->distPath = get_template_directory() . '/dist';

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets()
    {
        if (\App\Helpers\Environment::isDevelopment()) {
            $this->enqueueDevelopmentAssets();
        } else {
            $this->enqueueProductionAssets();
        }
    }

    private function enqueueDevelopmentAssets()
    {
        // These connect WP to the Vite dev server
        wp_enqueue_script('vite-client', 'http://localhost:3000/@vite/client', [], null, false);
        wp_enqueue_script('vite-main', 'http://localhost:3000/src/main.js', [], null, false);
        
        // Adds type="module" to support Vite
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if (in_array($handle, ['vite-client', 'vite-main'])) {
                return '<script type="module" src="' . esc_url($src) . '"></script>';
            }
            return $tag;
        }, 10, 3);
    }

    private function enqueueProductionAssets()
    {
        $manifestPathV5 = $this->distPath . '/.vite/manifest.json';
        $manifestPathOld = $this->distPath . '/manifest.json';
        
        $pathToLoad = file_exists($manifestPathV5) ? $manifestPathV5 : (file_exists($manifestPathOld) ? $manifestPathOld : false);

        if (!$pathToLoad) {
            return;
        }

        $this->manifest = json_decode(file_get_contents($pathToLoad), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($this->manifest['src/main.js'])) {
            return;
        }

        $mainEntry = $this->manifest['src/main.js'];

        // Enqueue JS
        wp_enqueue_script('theme-main-js', $this->distUri . '/' . $mainEntry['file'], [], null, true);

        // Enqueue CSS
        if (isset($mainEntry['css'])) {
            foreach ($mainEntry['css'] as $index => $cssFile) {
                wp_enqueue_style('theme-main-css-' . $index, $this->distUri . '/' . $cssFile, [], null);
            }
        }
    }
}
