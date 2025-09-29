<?php

declare(strict_types=1);

namespace QuizScoringForms\Services;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class ErrorGenerator
 *
 * Handles generating error messages for the Wordpress dashboard
 *
 * Responsibilities:
 * - Logs error messages
 * - Stores error messages in a list
 *
 * This class is a utility for logging and storing error messages
 */

final class ErrorGenerator
{

    /**
     * PROPERTY
     * 
     * @array errors_list
     * Stores a list of error message to log and output to the Wordpress dashboard
     */

    private static array $errors_list = [];

    /**
     * METHOD - generate
     * 
     * Utilizes the WP_Error as Error class for logging errors
     * 
     * @param string $code_msg - Code Message
     * @param string $description_msg - Description Message
     * 
     * @return void
     */

    public static function generate($code_msg = null, $description_msg = null): void
    {
        if ($code_msg && $description_msg) {
            error_log($description_msg);
            self::$errors_list[] = new \WP_Error($code_msg, $description_msg);
        }
    }

    /**
     * METHOD - errorHTMLMsg
     * 
     * Utilizes the WP_Error as Error class for logging errors
     * 
     * @param string $msg - Message
     * 
     * @return void
     */

    public static function errorHTMLMsg($msg): string {
        return '<h2 class="quiz-scoring-form-err-msg">'. $msg.'<h2>';
    }

    /**
     * METHOD - display_errors
     * 
     * Outputs error messages to the WordPress admin dashboard.  Styling is applied for better readability.
     * 
     * @return void
     */
    
    public static function displayErrors(): void
    {

        // Style error notices

            echo '
                <style>
                    .wp-custom-api-notice-error {
                        font-size: min(0.875rem, 4.25vw) !important;
                        padding: 1em !important;
                        text-wrap: balance;
                    }
                </style>
            ';

        // Output notice errors

        foreach (self::$errors_list as $error) {
            echo '<div class="notice notice-error wp-custom-api-notice-error"><strong>' . esc_html($error->get_error_code()) . ':</strong> ' . esc_html($error->get_error_message()) . '</div>';
        }

        do_action('wp_custom_api_error_displayed', self::$errors_list);
    }
}