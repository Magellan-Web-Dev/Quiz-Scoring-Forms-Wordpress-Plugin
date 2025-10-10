<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Admin;

use QuizScoringForms\Config;
use QuizScoringForms\Core\Post\RegisterHandler as PostRegisterHandler;
use QuizScoringForms\UI\Admin\Settings as SettingsUI;
use QuizScoringForms\UI\Admin\MainInterface as AdminUI;

/** Prevent direct access */
if (!defined('ABSPATH')) exit;

/**
 * Class Initializer
 *
 * Master handler for the WordPress admin.
 *
 * Responsibilities:
 * - Register the sidebar menu and submenu pages.
 * - Delegate rendering to Admin UI.
 * - Instantiate PostRegisterHandler to manage quizzes.
 */
final class Initializer
{
    /**
     * @var PostRegisterHandler
     * 
     */
    public readonly PostRegisterHandler $postHandler;

    /**
     * @var SettingsUI
     * 
     */
    public readonly SettingsUI $settingsUI;

    /**
     * @var AdminUI
     * 
     */
    public readonly AdminUI $adminUI;

    /**
     * Initializer constructor.
     * 
     * @see https://developer.wordpress.org/reference/functions/add_action/
     */

    public function __construct()
    {
        // Initialize post handler (CPT registration, metaboxes, API)
        $this->postHandler = new PostRegisterHandler();

        // Initialize the settings UI
        $this->settingsUI = new SettingsUI();

        // Initialize the dashboard UI
        $this->adminUI = new AdminUI();

        // Register menu and settings
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register the main plugin menu and subpages.
     * 
     * @see https://developer.wordpress.org/reference/functions/add_menu_page/
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     */
    public function registerAdminMenu(): void
    {
        add_menu_page(
            Config::PLUGIN_NAME,
            Config::PLUGIN_NAME,
            'manage_options',
            Config::SLUG . '_admin',
            [$this->adminUI, 'renderHome'],
            'dashicons-welcome-learn-more',
            25
        );

        add_submenu_page(
            Config::SLUG . '_admin',
            'Settings',
            'Settings',
            'manage_options',
            'settings',
            [$this->adminUI, 'renderSettings']
        );

        add_submenu_page(
            Config::SLUG . '_admin',
            'Quizzes',
            'Quizzes',
            'edit_posts',
            'edit.php?post_type=' . Config::POST_TYPE
        );
    }

    /**
     * Register plugin settings using WordPress Settings API
     * 
     * @see https://developer.wordpress.org/reference/functions/register_setting/
     * @see https://developer.wordpress.org/reference/functions/add_settings_section/
     * @see https://developer.wordpress.org/reference/functions/add_settings_field/
     * 
     * @return void
     */
    public function registerSettings(): void
    {
        // -------------------------------
        // Section: Submission Email Settings
        // -------------------------------
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_logo');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_email_to');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_email_from');
        register_setting(Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_email_subject');

        add_settings_section(
            Config::PLUGIN_ABBREV . '_submission_email',
            'Submission Email Settings',
            function() {
                echo '<p>Configure the plugin email and branding settings.</p>';
            },
            Config::SLUG_UNDERSCORE
        );

        add_settings_field(Config::PLUGIN_ABBREV . '_logo', 'Logo', [$this->settingsUI, 'renderLogoField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_submission_email');
        add_settings_field(Config::PLUGIN_ABBREV . '_email_to', 'Email To', [$this->settingsUI, 'renderEmailToField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_submission_email');
        add_settings_field(Config::PLUGIN_ABBREV . '_email_from', 'Email From', [$this->settingsUI, 'renderEmailFromField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_submission_email');
        add_settings_field(Config::PLUGIN_ABBREV . '_email_subject', 'Email Subject', [$this->settingsUI, 'renderEmailSubjectField'], Config::SLUG_UNDERSCORE, Config::PLUGIN_ABBREV . '_submission_email');

        // -------------------------------
        // Section: Contact Fields
        // -------------------------------
        register_setting(
            Config::SLUG_UNDERSCORE,
            Config::PLUGIN_ABBREV . '_'.Config::CONTACT_FIELDS_SLUG.'',
            ['sanitize_callback' => [self::class, 'sanitizeContactFields']]
        );

        add_settings_section(
            Config::PLUGIN_ABBREV . '_'.Config::CONTACT_FIELDS_SLUG.'_section',
            'Contact Fields',
            function() {
                echo '<p>Set the contact fields that will be displayed on each '.Config::POST_TYPE.' form.  These will come first before the quiz questions.</p>';
            },
            Config::SLUG_UNDERSCORE
        );

        add_settings_field(
            Config::PLUGIN_ABBREV . '_'.Config::CONTACT_FIELDS_SLUG.'',
            'Fields',
            [$this->settingsUI, 'renderContactFields'],
            Config::SLUG_UNDERSCORE,
            Config::PLUGIN_ABBREV . '_'.Config::CONTACT_FIELDS_SLUG.'_section'
        );
    }

    /**
     * Sanitize contact fields
     * 
     * @param array $fields
     * @return array
     */
    public static function sanitizeContactFields($fields): array 
    {
        $clean = [];
        $idIncrementer = 1;
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (empty($field['name']) || empty($field['type'])) {
                    continue; // skip blanks
                }
                $entry = [
                    'id' => 'c' . $idIncrementer,
                    'name' => sanitize_text_field($field['name']),
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'type' => sanitize_text_field($field['type']),
                    'required' => !empty($field['required']),
                    'options' => []
                ];
                if (in_array($entry['type'], ['select','radio','checkbox'], true) && !empty($field['options'])) {
                    foreach ($field['options'] as $opt) {
                        if (!empty($opt['label']) && !empty($opt['value'])) {
                            $entry['options'][] = [
                                'label' => sanitize_text_field($opt['label']),
                                'value' => sanitize_text_field($opt['value']),
                            ];
                        }
                    }
                }
                $clean[] = $entry;
                $idIncrementer++;
            }
        }
        return $clean;
    }    
}