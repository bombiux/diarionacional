<?php

namespace App\Setup;

class Security
{
    public function __construct()
    {
        // Security Headers (production only)
        add_action('send_headers', [$this, 'addSecurityHeaders']);

        // Headers and general cleanup
        add_filter('style_loader_src', [$this, 'removeWpVersion'], 999);
        add_filter('script_loader_src', [$this, 'removeWpVersion'], 999);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11);

        // Emojis
        add_action('init', [$this, 'disableEmojis']);

        // Pingbacks & XML-RPC
        add_filter('pings_open', '__return_false');
        add_filter('xmlrpc_methods', [$this, 'disablePingbacksXmlrpc']);
        add_action('pre_ping', [$this, 'disableSelfPingbacks']);
        add_filter('wp_headers', [$this, 'removePingbackHeader']);
        add_filter('xmlrpc_enabled', '__return_false');

        // Security Errors
        add_filter('login_errors', [$this, 'genericLoginError']);
        add_action('init', [$this, 'disableEmailPublishing']);

        // Layouts
        add_filter('option_page_comments', '__return_zero');
        add_action('template_redirect', [$this, 'redirectCommentPagination']);
        remove_filter('the_title', 'capital_P_dangit', 11);
        remove_filter('the_content', 'capital_P_dangit', 11);
        remove_filter('comment_text', 'capital_P_dangit', 31);
        if (!defined('CORE_UPGRADE_SKIP_NEW_BUNDLED')) {
            define('CORE_UPGRADE_SKIP_NEW_BUNDLED', true);
        }

        // Admin
        add_action('admin_bar_menu', [$this, 'removeWpLogo'], 999);
        add_action('admin_enqueue_scripts', [$this, 'disableImageEditor']);
        add_filter('admin_email_check_interval', '__return_false');
        remove_action('welcome_panel', 'wp_welcome_panel');
        add_action('admin_head', [$this, 'hideUpdateNoticesForNonAdmins']);

        // Block direct access to wp-login.php (use theme's custom login modal)
        add_action('login_init', [$this, 'blockDefaultLogin']);
    }

    public function removeWpVersion($src)
    {
        return preg_replace('/(\?ver=)[^\s&]+/', '', $src);
    }

    public function disableEmojis()
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('wp_resource_hints', [$this, 'removeEmojisDnsPrefetch'], 10, 2);
        add_filter('tiny_mce_plugins', [$this, 'removeEmojisTinymce']);
    }

    public function removeEmojisDnsPrefetch($urls, $relation_type)
    {
        if ('dns-prefetch' === $relation_type) {
            $urls = array_filter($urls, function ($url) {
                return false === strpos($url, 'https://s.w.org/images/core/emoji/');
            });
        }
        return $urls;
    }

    public function removeEmojisTinymce($plugins)
    {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        }
        return $plugins;
    }

    public function disablePingbacksXmlrpc($methods)
    {
        unset($methods['pingback.ping']);
        unset($methods['pingback.extensions.getPingbacks']);
        return $methods;
    }

    public function disableSelfPingbacks(&$links)
    {
        $home = home_url();
        foreach ($links as $l => $link) {
            if (0 === strpos($link, $home)) {
                unset($links[$l]);
            }
        }
    }

    public function removePingbackHeader($headers)
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function genericLoginError()
    {
        return 'Los datos de acceso no son correctos.';
    }

    public function disableEmailPublishing()
    {
        if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === 'wp-mail.php') {
            wp_die('Esta funcionalidad está desactivada.', 'Acceso no permitido', ['response' => 403]);
        }
    }

    public function redirectCommentPagination()
    {
        if (is_singular() && get_query_var('cpage')) {
            wp_redirect(get_permalink(), 301);
            exit;
        }
    }

    public function removeWpLogo($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    public function disableImageEditor($hook)
    {
        if (in_array($hook, ['post.php', 'post-new.php', 'upload.php'], true)) {
            wp_dequeue_script('image-edit');
            wp_deregister_script('image-edit');
        }
    }

    public function hideUpdateNoticesForNonAdmins()
    {
        if (!current_user_can('update_core')) {
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('admin_notices', 'maintenance_nag', 10);
        }
    }

    public function addSecurityHeaders()
    {
        // Only add strict headers in production
        if (\App\Helpers\Environment::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
        header("X-XSS-Protection: 1; mode=block");
    }

    /**
     * Block direct access to wp-login.php for non-logged-in users.
     * Allows: logout action, admin AJAX, and already-logged-in admins.
     */
    public function blockDefaultLogin()
    {
        // Always allow logout requests
        $action = $_REQUEST['action'] ?? '';
        if ($action === 'logout' || $action === 'postpass') {
            return;
        }

        // Allow logged-in administrators to access wp-admin normally
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }

        // Block everyone else — redirect to homepage
        wp_redirect(home_url('/'));
        exit;
    }
}
