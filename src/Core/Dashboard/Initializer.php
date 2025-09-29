<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Dashboard;

use QuizScoringForms\Config;
use QuizScoringForms\Core\Post\RegisterHandler as PostRegisterHandler;
use QuizScoringForms\UI\Dashboard\MainInterface as DashboardUI;
use QuizScoringForms\UI\Dashboard\Settings as SettingsUI;

/** Prevent direct access */
if (!defined('ABSPATH')) exit;

/**
 * Class Initializer
 *
 * Master handler for the WordPress dashboard.
 *
 * Responsibilities:
 * - Register the sidebar menu and submenu pages.
 * - Delegate rendering to DashboardUI.
 * - Instantiate PostRegisterHandler to manage quizzes.
 */
final class Initializer
{
    private PostRegisterHandler $postHandler;

    public function __construct()
    {
        // Initialize post handler (CPT registration, metaboxes, API)
        $this->postHandler = new PostRegisterHandler();

        // Register menu and settings
        add_action('admin_menu', [$this, 'registerDashboardMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register the main plugin menu and subpages.
     */
    public function registerDashboardMenu(): void
    {
        add_menu_page(
            Config::PLUGIN_NAME,
            Config::PLUGIN_NAME,
            'manage_options',
            Config::SLUG . '_dashboard',
            [DashboardUI::class, 'renderHome'],
            'dashicons-welcome-learn-more',
            25
        );

        add_submenu_page(
            Config::SLUG . '_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'settings',
            [DashboardUI::class, 'renderSettings']
        );

        add_submenu_page(
            Config::SLUG . '_dashboard',
            'Quizzes',
            'Quizzes',
            'edit_posts',
            'edit.php?post_type=' . Config::POST_TYPE
        );
    }

    /**
     * Register plugin settings using WordPress Settings API
     */
    public function registerSettings(): void
    {
        // Register settings
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'logo');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'email_to');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'email_from');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'email_subject');

        // Settings section
        add_settings_section(
            Config::PLUGIN_ABBREV . 'main_settings',
            'Main Settings',
            function() { echo '<p>Configure the plugin email and branding settings.</p>';},
            Config::SLUG_UNDERSCORE
        );

        // Individual fields
        add_settings_field(Config::PLUGIN_ABBREV . 'logo', 'Logo', [SettingsUI::class, 'renderLogoField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'main_settings');
        add_settings_field(Config::PLUGIN_ABBREV . 'email_to', 'Email To', [SettingsUI::class, 'renderEmailToField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'main_settings');
        add_settings_field(Config::PLUGIN_ABBREV . 'email_from', 'Email From', [SettingsUI::class, 'renderEmailFromField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'main_settings');
        add_settings_field(Config::PLUGIN_ABBREV . 'email_subject', 'Email Subject', [SettingsUI::class, 'renderEmailSubjectField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . 'main_settings');
    }
}
