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

/**
 * Class Plugin
 * 
 * Handles the initialization of the plugin
 */

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
     * Initialize the plugin. Makes sure that the minimum PHP version is met.
     * 
     */
    public static function init() {
        try {
            if (self::$initialized) return;
            if (!self::minPHPVersionVerify()) return;
            self::loadPlugin();
        } catch (\LogicException $e) {
            error_log($e->getMessage());
        } finally {
            self::$initialized = true;
            ErrorGenerator::displayErrors();
        }
    }

    /**
     * Verify minimum PHP version.  
     * 
     * @return bool
     */
    private static function minPHPVersionVerify(): bool {
        if (!version_compare(PHP_VERSION, '8.1.0', '>=')) {
            ErrorGenerator::generate('Quiz Scoring Forms is currently not running', 'This plugin requires PHP version 8.1 or higher to be installed.');
            return false;
        }

        return true;
    }

    /**
     * Load the plugin
     * 
     * @return void
     */
    private static function loadPlugin(): void {
        self::$dashboard = new Dashboard();
        self::$shortcode= new Shortcode();
    }

    /**
     * Get plugin initialization data
     * 
     * @return array
     */
    public static function getData(): array {
        return [
            'dashboard' => self::$dashboard,
            'shortcode' => self::$shortcode
        ];
    }
}