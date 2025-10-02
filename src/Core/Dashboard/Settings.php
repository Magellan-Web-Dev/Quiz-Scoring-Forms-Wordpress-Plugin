<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Dashboard;

use QuizScoringForms\Config;
use QuizScoringForms\UI\Dashboard\Settings as SettingsUI;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class SettingsHandler
 *
 * Handles registration and sanitization of plugin settings.
 * Delegates HTML rendering to SettingsUI.
 */
final class Settings
{
    private const SETTINGS_GROUP = Config::PLUGIN_ABBREV . '_settings_group';

    public function __construct()
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register plugin settings and sections
     */
    public function registerSettings(): void
    {
        // Register settings
        register_setting(self::SETTINGS_GROUP, Config::PLUGIN_ABBREV . '_logo', [
            'sanitize_callback' => [$this, 'sanitizeLogo']
        ]);
        register_setting(self::SETTINGS_GROUP, Config::PLUGIN_ABBREV . '_email_to', [
            'sanitize_callback' => 'sanitize_email'
        ]);
        register_setting(self::SETTINGS_GROUP, Config::PLUGIN_ABBREV . '_email_from', [
            'sanitize_callback' => 'sanitize_email'
        ]);
        register_setting(self::SETTINGS_GROUP, Config::PLUGIN_ABBREV . '_email_subject', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);

        // Settings section
        add_settings_section(
            Config::PLUGIN_ABBREV . '_main_settings',
            'Main Settings',
            [SettingsUI::class, 'renderSectionDescription'],
            self::SETTINGS_GROUP
        );

        // Settings fields
        add_settings_field(
            Config::PLUGIN_ABBREV . '_logo',
            'Logo',
            [SettingsUI::class, 'renderLogoField'],
            self::SETTINGS_GROUP,
            'qsf_main_settings'
        );

        add_settings_field(
            Config::PLUGIN_ABBREV . '_email_to',
            'Email To',
            [SettingsUI::class, 'renderEmailToField'],
            self::SETTINGS_GROUP,
            'qsf_main_settings'
        );

        add_settings_field(
            Config::PLUGIN_ABBREV . '_email_from',
            'Email From',
            [SettingsUI::class, 'renderEmailFromField'],
            self::SETTINGS_GROUP,
            'qsf_main_settings'
        );

        add_settings_field(
            Config::PLUGIN_ABBREV . '_email_subject',
            'Email Subject',
            [SettingsUI::class, 'renderEmailSubjectField'],
            self::SETTINGS_GROUP,
            'qsf_main_settings'
        );
    }

    /**
     * Sanitize logo input (allow only URL strings)
     */
    public function sanitizeLogo($value): string
    {
        return esc_url_raw($value);
    }

    /**
     * Get the settings group name
     */
    public static function getSettingsGroup(): string
    {
        return self::SETTINGS_GROUP;
    }
}