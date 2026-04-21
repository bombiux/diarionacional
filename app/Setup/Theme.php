<?php

namespace App\Setup;

use Timber\Timber;

class Theme
{
    public function __construct()
    {
        // Initialize Timber
        Timber::init();
        Timber::$dirname = ['views', 'templates'];
        
        add_action('after_setup_theme', [$this, 'setupThemeSupports']);
        add_filter('timber/context', [$this, 'addGlobalContext']);
        add_filter('timber/twig', [$this, 'registerTwigFunctions']);
        add_filter('timber/twig/environment/options', [$this, 'disableTwigCacheInDev']);
        
        // Use custom avatar if available
        add_filter('get_avatar_url', [$this, 'useCustomAvatarUrl'], 10, 3);
    }

    /**
     * Use Carbon Fields user avatar if set.
     */
    public function useCustomAvatarUrl($url, $id_or_email, $args)
    {
        $user_id = 0;
        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
            $user_id = $user->ID;
        } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        }

        if ($user_id) {
            $custom_avatar = carbon_get_user_meta($user_id, 'crb_user_avatar');
            if ($custom_avatar) {
                return $custom_avatar;
            }
        }

        return $url;
    }

    /**
     * Registra funciones personalizadas en Twig.
     */
    public function registerTwigFunctions($twig)
    {
        $twig->addFunction(new \Twig\TwigFunction('icon', function ($name, $class = '') {
            $path = get_template_directory() . "/assets/bootstrap-icons/{$name}.svg";

            if (!file_exists($path)) {
                return "<!-- Icono {$name} no encontrado en {$path} -->";
            }

            $svg = file_get_contents($path);

            if ($class) {
                // Remove existing width, height and class attributes to let Tailwind rule
                $svg = preg_replace('/\s+(width|height|class)=["\'][^"\']*["\']/i', '', $svg);
                // Inject the new class into the <svg> tag
                $svg = preg_replace('/<svg([^>]*)/i', '<svg$1 class="' . esc_attr($class) . '"', $svg);
            }

            return $svg;
        }));

        return $twig;
    }

    public function addGlobalContext($context)
    {
        // Only expose login AJAX data for non-logged-in users
        if (!is_user_logged_in()) {
            $context['ajax_url'] = admin_url('admin-ajax.php');
            $context['login_nonce'] = wp_create_nonce('ajax_login_nonce');
        }

        // Make primary nav menu available globally as 'menu'
        $context['menu'] = Timber::get_menu('primary') ?? Timber::get_menu('Primary Menu') ?? Timber::get_menu('header-menu');

        // Current user info
        $currentUser = wp_get_current_user();
        if ($currentUser->exists()) {
            $context['user'] = [
                'id' => $currentUser->ID,
                'name' => $currentUser->display_name,
                'avatar' => get_avatar_url($currentUser->ID, ['size' => 128]),
                'roles' => $currentUser->roles,
            ];
        }

        // Default categories for the menu if not set in WP
        $context['categories_menu'] = [
            ['title' => 'Nacionales', 'link' => home_url('/category/nacionales/')],
            ['title' => 'Internacionales', 'link' => home_url('/category/internacionales/')],
            ['title' => 'Deportes', 'link' => home_url('/category/deportes/')],
            ['title' => 'Tecnología', 'link' => home_url('/category/tecnologia/')],
            ['title' => 'Farándula', 'link' => home_url('/category/farandula/')],
        ];

        return $context;
    }

    public function disableTwigCacheInDev($options)
    {
        if (\App\Helpers\Environment::isDevelopment()) {
            $options['cache'] = false;
            $options['auto_reload'] = true;
        }
        return $options;
    }

    public function setupThemeSupports()
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        
        // Custom Image Sizes para DBarricada
        add_image_size('diario-nacional-hero', 1200, 675, true); // 16:9 para portadas principales
        add_image_size('diario-nacional-card', 600, 400, true);  // 3:2 para tarjetas de noticias
        add_image_size('diario-nacional-thumb', 300, 200, true); // 3:2 para widgets/sidebar
        
        add_theme_support('html5', [
            'comment-list',
            'comment-form',
            'search-form',
            'gallery',
            'caption',
            'style',
            'script'
        ]);
        add_theme_support('menus');
    }
}
