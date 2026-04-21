<?php

namespace App\Setup;

/**
 * Class Routes
 * Handles custom rewrite rules and template routing.
 */
class Routes
{
    public function __construct()
    {
        add_action('init', [$this, 'registerRewriteRules']);
        add_filter('template_include', [$this, 'resolveCustomTemplates']);
    }

    /**
     * Register custom rewrite rules for pages that don't exist as WP pages.
     */
    public function registerRewriteRules()
    {
        add_rewrite_rule('^archivo-historia/?$', 'index.php?pagename=archivo-historia', 'top');
        add_rewrite_tag('%archivo_historia%', '1');
    }

    /**
     * Resolve custom templates based on query vars.
     */
    public function resolveCustomTemplates($template)
    {
        if (get_query_var('pagename') === 'archivo-historia') {
            $custom_template = locate_template('archivo-historia.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        return $template;
    }
}
