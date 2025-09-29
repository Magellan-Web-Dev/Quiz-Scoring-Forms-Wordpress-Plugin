<?php

declare(strict_types=1);

namespace QuizScoringForms;

use QuizScoringForms\Core\Dashboard\Initializer as Dashboard;
use QuizScoringForms\Core\Shortcode\Initializer as Shortcode;
use QuizScoringForms\Services\ErrorGenerator;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Plugin {

    /**
     * Initialized  
     */
    public static bool $initialized = false;

    /**
     * Dashboard Interface initializer instantiation
     */
    private static Dashboard $dashboard;

    /**
     * Shortcode Interface initializer instantiation
     */
    private static Shortcode $shortcode;


    /**
     * Initialize the plugin
     * 
     */
    public static function init() {
        try {
            if (self::$initialized) return;
            self::minPHPVersionVerify();
            self::loadPlugin();
        } catch (\LogicException $e) {
            echo $e->getMessage();
        } finally {
            self::$initialized = true;
            ErrorGenerator::displayErrors();
        }
    }

    private static function minPHPVersionVerify() {
        if (!version_compare(PHP_VERSION, '8.1.0', '>=')) {
            ErrorGenerator::generate('Quiz Scoring Forms is currently not running', 'This plugin requires PHP version 8.1 or higher to be installed.');
            return;
        }
    }

    private static function loadPlugin() {
        self::$dashboard = new Dashboard();
        self::$shortcode= new Shortcode();
    }
}