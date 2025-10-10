<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Shortcode;

use QuizScoringForms\Config;
use QuizScoringForms\Core\Shortcode\Data as ShortcodeData;
use QuizScoringForms\Core\Form\DataHandler as FormDataHandler;
use QuizScoringForms\UI\Shortcode\Render;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Initializer
 *
 * Master handler for the shortcode.
 *
 * Responsibilities:
 * - Registers the shortcode with WordPress.
 * - Fetches the quiz post data using the ShortcodeData class.
 * - Validates and sanitizes the quiz post data using the FormDataHandler class.
 * - Renders the quiz form using the Render class.
 */
final class Initializer 
{
    /**
     * The quiz post data.
     *
     * @var ShortcodeData
     */
    public readonly ShortcodeData $data;

    /**
     * The form data handler.
     *
     * @var FormDataHandler
     */
    public readonly FormDataHandler $formData;

    /**
     * The render class.
     * 
     * @var Render
     */
    public readonly Render $render;

    /**
     * Registers the shortcode with WordPress.
     */
    public function __construct() 
    {
        add_shortcode(Config::SLUG, [$this, 'shortcodeCallback']);
    }

    /**
     * Callback function for the shortcode.
     *
     * Fetches the quiz post data, validates and sanitizes it, and renders the quiz form.
     *
     * @param array $atts
     * @return string
     */
    public function shortcodeCallback(array $atts) 
    {
        $this->data = ShortcodeData::get($atts);
        if (!$this->data) {
            return Render::renderErrMsg('No quiz post data could be found.');
        }
        $this->formData = new FormDataHandler((array) $this->data);
        $this->render = new Render($this->formData);
        $this->render->renderContent();
    }
}
