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
    public const PLUGIN_NAME = 'Quiz Scoring Forms';
    public const SLUG = 'quiz-scoring-forms';
    public const SLUG_UNDERSCORE = 'quiz_scoring_forms';
    public const PLUGIN_ABBREV = 'qsf';
    public const POST_TYPE = 'quiz';
    public const POST_NAME = 'Quizzes';
    public const POST_NAME_SINGULAR = 'Quiz';
    public const NAMESPACE_PREFIX = 'QuizScoringForms\\';
}