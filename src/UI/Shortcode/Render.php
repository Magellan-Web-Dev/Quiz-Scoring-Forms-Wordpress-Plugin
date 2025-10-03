<?php

declare(strict_types=1);

namespace QuizScoringForms\Ui\Shortcode;

use Dom\HTMLElement;
use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Render 
{
    /**
     * Render error message if shortcode data could not be found
     */
    public static function renderErrMsg($msg): string {
        return '<h3 class="'.Config::PLUGIN_ABBREV.'-err-msg err-msg">'. esc_html($msg).'</h3>';
    }
}