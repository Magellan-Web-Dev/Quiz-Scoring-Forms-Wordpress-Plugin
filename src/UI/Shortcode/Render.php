<?php

declare(strict_types=1);

namespace QuizScoringForms\Ui\Shortcode;

use Dom\HTMLElement;
use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Render {
    public static function renderErrMsg($msg): string {
        return '<h3 class="'.Config::SLUG.'-err-msg err-msg">'. esc_html($msg).'</h3>';
    }
}