<?php

declare(strict_types=1);

namespace QuizScoringForms\UI\Dashboard;

use QuizScoringForms\Config;

/** Prevent direct access */
if (!defined('ABSPATH')) exit;

/**
 * Class SettingsUI
 *
 * Handles HTML rendering for plugin settings fields and sections.
 */
final class Settings
{
    /**
     * Render section description
     */
    public static function renderSectionDescription(): void
    {
        echo '<p>Configure the plugin logo and email settings below.</p>';
    }

    /**
     * Render logo field with media uploader button
     */
    public static function renderLogoField(): void
    {
        $logo = get_option(Config::PLUGIN_ABBREV . '_logo', '');
        ?>
        <input type="text" name="<?php Config::PLUGIN_ABBREV ?>'_logo" value="<?= esc_attr($logo) ?>" style="width:50%;">
        <button class="button upload-logo">Upload</button>
        <?php
    }

    /**
     * Render Email To field
     */
    public static function renderEmailToField(): void
    {
        $value = get_option('qsf_email_to', '');
        echo '<input type="email" name="'.Config::PLUGIN_ABBREV.'_email_to" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render Email From field
     */
    public static function renderEmailFromField(): void
    {
        $value = get_option('qsf_email_from', '');
        echo '<input type="email" name="'.Config::PLUGIN_ABBREV.'_email_from" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render Email Subject field
     */
    public static function renderEmailSubjectField(): void
    {
        $value = get_option('qsf_email_subject', '');
        echo '<input type="text" name="'.Config::PLUGIN_ABBREV.'_email_subject" value="' . esc_attr($value) . '" style="width:50%;">';
    }
}