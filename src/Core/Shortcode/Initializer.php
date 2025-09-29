<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Shortcode;

use QuizScoringForms\Config;
use QuizScoringForms\Services\ErrorGenerator;
use QuizScoringForms\Core\Form\FormDataHandler;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Initializer {

    private string $slug;

    public function __construct() {
        add_shortcode(Config::SLUG, [$this, 'shortcodeCallback']);
    }

    private function shortcodeCallback($atts) {
        $slug= $atts['slug'] ?? null;

        if (!$slug) {
            return ErrorGenerator::errorHTMLMsg('A shortcode attribute "slug" must be provided with the quiz corresponding slug name.');
        }

        $this->$slug = $slug;
    }

    public function getSlug() {
        return $this->slug;
    }
}