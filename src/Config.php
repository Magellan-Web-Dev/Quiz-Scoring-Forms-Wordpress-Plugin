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

final class Config {
    public const PLUGIN_NAME = 'Quiz Scoring Forms'; // The name of the plugin
    public const MIN_PHP_VERSION = '8.1.0'; // The minimum PHP version required
    public const SLUG = 'quiz-scoring-forms'; // The slug of the plugin
    public const SLUG_UNDERSCORE = 'quiz_scoring_forms'; // The underscored slug of the plugin
    public const PLUGIN_ABBREV = 'qsf'; // The abbreviation of the plugin
    public const POST_TYPE = 'quiz'; // The post type of the plugin
    public const POST_NAME = 'Quizzes'; // The name of the post type
    public const POST_NAME_SINGULAR = 'Quiz'; // The singular name of the post type
    public const NAMESPACE_PREFIX = 'QuizScoringForms\\'; // The namespace prefix
    public const CONTACT_FIELDS_SLUG = 'contact_fields'; // The slug for contact fields
}