<?php

/**
 * Plugin Name: Quiz Scoring Forms
 * Description: This plugin provides a quiz form with sections/questions that are then tallied to produce a score.
 * Author: Chris Paschall
 * Version: 1.0.0
 * PHP Version Minimum: 8.1
 */

use QuizScoringForms\Config;
use QuizScoringForms\Plugin;
use QuizScoringForms\Autoloader;

/**
 * Enable dev mode
 */

define('QUIZ_SCORING_FORMS_DEV_MODE', true);

/** 
 * In dev mode (non-WP environment), bootstrap your own "ABSPATH equivalent".
 */

if (!defined('ABSPATH') && defined('QUIZ_SCORING_FORMS_DEV_MODE') && QUIZ_SCORING_FORMS_DEV_MODE === true) { 
    define('ABSPATH', true);
}

/** 
 * Prevent direct access from sources other than the Wordpress environment.
 */

if (!defined('ABSPATH')) exit;

/** 
 * Define WP Custom API Plugin Folder Path.  Used for requiring plugin files and auto loader on init class.
 */

if (function_exists('plugin_dir_path') && !QUIZ_SCORING_FORMS_DEV_MODE) {
    define("QUIZ_SCORING_FORMS_FOLDER_PATH", 
        preg_replace(
            '#/+#', 
            '/',
            str_replace("\\", "/", plugin_dir_path(__FILE__))
        )
    );
} else {
    define("QUIZ_SCORING_FORMS_FOLDER_PATH", __DIR__);
}

/**
 * Load config class
 */

require_once QUIZ_SCORING_FORMS_FOLDER_PATH . '/src/Config.php';

/**
 * Load Autoloader
 */

require_once QUIZ_SCORING_FORMS_FOLDER_PATH . '/src/Autoloader.php';

/**
 * Run Autoloader
 */

Autoloader::register(Config::NAMESPACE_PREFIX, QUIZ_SCORING_FORMS_FOLDER_PATH . '/src/');

/**
 * Load Plugin Init
 */

require_once QUIZ_SCORING_FORMS_FOLDER_PATH . '/src/Plugin.php';

/**
 * Run Plugin
 */

Plugin::init();