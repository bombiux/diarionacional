<?php

namespace App\Helpers;

/**
 * Centralized environment detection helper.
 * Avoids duplicating WP_ENV + fsockopen logic across multiple classes.
 */
class Environment
{
    private static ?bool $isDevelopment = null;

    /**
     * Check if the current environment is development.
     * Result is cached for the duration of the request.
     */
    public static function isDevelopment(): bool
    {
        if (self::$isDevelopment !== null) {
            return self::$isDevelopment;
        }

        // Check WP_ENV in multiple locations
        if (isset($_SERVER['WP_ENV']) && $_SERVER['WP_ENV'] === 'development') {
            return self::$isDevelopment = true;
        }
        if (isset($_ENV['WP_ENV']) && $_ENV['WP_ENV'] === 'development') {
            return self::$isDevelopment = true;
        }
        if (getenv('WP_ENV') === 'development') {
            return self::$isDevelopment = true;
        }

        // Fallback: ping Vite dev server
        $connection = @fsockopen('127.0.0.1', 3000, $errno, $errstr, 0.1);
        if (is_resource($connection)) {
            fclose($connection);
            return self::$isDevelopment = true;
        }

        $connection = @fsockopen('localhost', 3000, $errno, $errstr, 0.1);
        if (is_resource($connection)) {
            fclose($connection);
            return self::$isDevelopment = true;
        }

        return self::$isDevelopment = false;
    }

    /**
     * Check if the current environment is production.
     */
    public static function isProduction(): bool
    {
        return !self::isDevelopment();
    }
}
