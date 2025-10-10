<?php

declare(strict_types=1);

namespace QuizScoringForms;

use QuizScoringForms\Core\Admin\Initializer as Admin;
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

/**
 * Class Plugin
 * 
 * Handles the initialization of the plugin
 * 
 * @package QuizScoringForms
 */
final class Plugin 
{

    /**
     * @var Plugin|null $instance
     */
    private static ?Plugin $instance = null;

    /**
     * @var Admin $admin
     */
    private Admin $admin;

    /**
     * @var Shortcode $shortcode
     */
    private Shortcode $shortcode;

    /**
     * Initialize the plugin
     */
    public static function init(): void 
    {
        if (self::$instance !== null) return;

        if (!self::minPHPVersionVerify()) return;

        self::$instance = new self();
        self::$instance->loadPlugin();

        ErrorGenerator::displayErrors();
    }

    /**
     * Get the current instance of the plugin
     * 
     * @return static
     */
    public static function instance(): self 
    {
        if (self::$instance === null) {
            throw new \LogicException("Plugin not initialized yet.");
        }
        return self::$instance;
    }

    /**
     * Verify minimum PHP version
     * 
     * @return bool
     */
    private static function minPHPVersionVerify(): bool 
    {
        if (!version_compare(PHP_VERSION, Config::MIN_PHP_VERSION, '>=')) {
            ErrorGenerator::generate('Quiz Scoring Forms is currently not running', 'This plugin requires PHP version 8.1 or higher to be installed.');
            return false;
        }

        return true;
    }

    /**
     * Load the plugin components
     */
    private function loadPlugin(): void 
    {
        $this->admin = new Admin();
        $this->shortcode = new Shortcode();
    }

    /**
     * Get the admin dashboard component
     * 
     * @return Admin
     */
    public function getDashboard(): Admin 
    {
        return $this->admin;
    }

    /**
     * Get the shortcode component
     * 
     * @return Shortcode
     */
    public function getShortcode(): Shortcode 
    {
        return $this->shortcode;
    }
}