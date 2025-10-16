<?php

declare(strict_types=1);

namespace QuizScoringForms;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Config
 * 
 * Contains configuration values for the plugin.
 */

final class Config 
{
    public const string PLUGIN_NAME = 'Quiz Scoring Forms'; // The name of the plugin
    public const string VERSION = '1.0.0'; // The version of the plugin
    public const string MIN_PHP_VERSION = '8.1.0'; // The minimum PHP version required
    public const string SLUG = 'quiz-scoring-forms'; // The slug of the plugin
    public const string SLUG_UNDERSCORE = 'quiz_scoring_forms'; // The underscored slug of the plugin
    public const string PLUGIN_ABBREV = 'qsf'; // The abbreviation of the plugin.  This is used for HTML ID's and class names to prevent namespace conflicts
    public const string POST_TYPE = 'quiz'; // The post type of the plugin
    public const string POST_NAME = 'Quizzes'; // The name of the post type
    public const string POST_NAME_SINGULAR = 'Quiz'; // The singular name of the post type
    public const string NAMESPACE_PREFIX = 'QuizScoringForms\\'; // The namespace prefix
    public const string CONTACT_FIELDS_SLUG = 'contact_fields'; // The slug for contact fields
    public const string ASSETS_PATH = QUIZ_SCORING_FORMS_FOLDER_PATH . '/assets'; // The name of the assets folder
}