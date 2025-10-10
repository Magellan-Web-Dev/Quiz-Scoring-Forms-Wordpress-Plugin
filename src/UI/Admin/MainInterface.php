<?php

declare(strict_types=1);

namespace QuizScoringForms\UI\Admin;

use QuizScoringForms\Config;

/** Prevent direct access */
if (!defined('ABSPATH')) exit;

/**
 * Class DashboardUI
 *
 * Handles all HTML rendering for the plugin admin dashboard.
 */
final class MainInterface
{
    /**
     * Render dashboard home page
     */
    public function renderHome(): void
    {
        $settings_url = admin_url('admin.php?page=settings');
        $quizzes_url  = admin_url('edit.php?post_type=' . Config::POST_TYPE);
        ?>
        <div class="wrap">
            <h1><?= esc_html(Config::PLUGIN_NAME) ?></h1>
            <p>Welcome to the plugin dashboard! Use the menu to navigate.</p>
            <a class="button button-primary" href="<?= esc_url($settings_url) ?>">Go To Settings</a>
            <a class="button" href="<?= esc_url($quizzes_url) ?>">Go To <?= ucfirst(Config::POST_TYPE) ?> Posts</a>
        </div>
        <?php
    }

    /**
     * Render plugin settings page
     */
    public function renderSettings(): void
    {
        ?>
        <div class="wrap">
            <h1><?= esc_html(Config::PLUGIN_NAME) ?> Settings</h1>
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php
                settings_fields(Config::SLUG_UNDERSCORE);
                do_settings_sections(Config::SLUG_UNDERSCORE);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // === Field renderers ===
    
    /**
     * Render logo field
     */
    public function renderLogoField(): void
    {
        $logo = get_option(Config::PLUGIN_ABBREV . 'logo', '');
        echo '<input type="text" name="qsf_logo" value="' . esc_attr($logo) . '" style="width:50%;"> ';
        echo '<button class="button upload-logo">Upload</button>';
    }

    /**
     * Render email to field
     */
    public function renderEmailToField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . 'email_to', '');
        echo '<input type="email" name="qsf_email_to" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render email from field
     */
    public function renderEmailFromField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . 'email_from', '');
        echo '<input type="email" name="qsf_email_from" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render email subject field
     */
    public function renderEmailSubjectField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . 'email_subject', '');
        echo '<input type="text" name="qsf_email_subject" value="' . esc_attr($value) . '" style="width:50%;">';
    }
}
