<?php

declare(strict_types=1);

namespace QuizScoringForms;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Custom PSR-4 Autoloader for QuizScoringForms
*/

final class Autoloader
{
    /**
     * Initialized  
     */
    private static bool $initialized = false;

    /**
     * Base namespace prefix
     */
    private static string $prefix;

    /**
     * Base directory for namespace root
     */
    private static string $baseDir;

    /**
     * Register the autoloader with SPL. Allow registration only once.
     */
    public static function register($prefix, $baseDir): void
    {
        if (self::$initialized) return;
        self::$prefix = $prefix;
        self::$baseDir = $baseDir;
        spl_autoload_register([__CLASS__, 'loadClass']);
        self::$initialized = true;
    }

    /**
     * Loads the given class file if it matches the namespace prefix
     */
    private static function loadClass(string $class): void
    {
        // Does the class use our namespace prefix?
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return; // Not our namespace, skip
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Replace namespace separators with directory separators
        $file = self::$baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        // Require file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
