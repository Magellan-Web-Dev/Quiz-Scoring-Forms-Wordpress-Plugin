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

final class Initializer {

    public readonly ShortcodeData $data;
    public readonly FormDataHandler $formData;

    public function __construct() 
    {
        add_shortcode(Config::SLUG, [$this, 'shortcodeCallbackInit']);
    }

    public function shortcodeCallbackInit(array $atts) 
    {
        $this->data = ShortcodeData::get($atts);
        if (!$this->data) {
            return Render::renderErrMsg('No quiz post data could be found.');
        }
        $this->formData = new FormDataHandler((array) $this->data);
    }
}