<?php

namespace App\Setup;

/**
 * Class Auth
 * Handles AJAX login functionality for the theme.
 */
class Auth
{
    public function __construct()
    {
        // Register AJAX actions for logged-in and non-logged-in users
        add_action('wp_ajax_nopriv_ajax_login', [$this, 'ajaxLogin']);
        add_action('wp_ajax_ajax_login', [$this, 'ajaxLogin']);
    }

    /**
     * Handle the AJAX login request.
     */
    public function ajaxLogin()
    {
        // First check the nonce for security
        check_ajax_referer('ajax_login_nonce', 'security');

        // Rate limiting: max 5 attempts per 15 minutes per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $transient_key = 'login_attempts_' . md5($ip);
        $attempts = (int) get_transient($transient_key);

        if ($attempts >= 5) {
            wp_send_json_error([
                'message' => 'Demasiados intentos de inicio de sesión. Por favor, espera 15 minutos.',
            ]);
            wp_die();
        }

        // Increment attempt counter (expires in 15 minutes)
        set_transient($transient_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);

        // Collect and sanitize the data
        $info = [
            'user_login'    => sanitize_user($_POST['username'] ?? ''),
            'user_password' => $_POST['password'] ?? '',
            'remember'      => isset($_POST['remember']) && ($_POST['remember'] === 'true' || $_POST['remember'] === '1' || $_POST['remember'] === 1),
        ];

        // Sign the user in — respect current SSL state
        $user_signon = wp_signon($info, is_ssl());

        if (is_wp_error($user_signon)) {
            wp_send_json_error([
                'message' => 'Usuario o contraseña incorrectos. Por favor, inténtalo de nuevo.',
            ]);
        } else {
            // Clear rate limit on successful login
            delete_transient($transient_key);

            wp_send_json_success([
                'message' => '¡Inicio de sesión exitoso! Redirigiendo...',
            ]);
        }

        wp_die();
    }

    /**
     * Optional: Helper to generate the login nonce.
     */
    public static function getNonce()
    {
        return wp_create_nonce('ajax_login_nonce');
    }
}
