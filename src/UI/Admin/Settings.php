<?php

declare(strict_types=1);

namespace QuizScoringForms\UI\Admin;

use QuizScoringForms\Config;

/** Prevent direct access */
if (!defined('ABSPATH')) exit;

/**
 * Class Settings
 * 
 * Handles rendering of the plugin settings page.
 */
final class Settings
{
    /**
     * Render Logo Field UI
     * 
     * @return void
     */
    public function renderLogoField(): void
    {
        $logo = get_option(Config::PLUGIN_ABBREV . '_logo', '');
        ?>
        <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_logo" value="<?= esc_attr($logo) ?>" style="width:50%;">
        <button class="button upload-logo">Upload</button>
        <?php
    }

    /**
     * Render Email Fields UI
     * 
     * @return void
     */
    public function renderEmailToField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . '_email_to', '');
        echo '<input type="email" name="'.Config::PLUGIN_ABBREV.'_email_to" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render Email Fields UI
     * 
     * @return void
     */
    public function renderEmailFromField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . '_email_from', '');
        echo '<input type="email" name="'.Config::PLUGIN_ABBREV.'_email_from" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render Email Fields UI
     * 
     * @return void
     */
    public function renderEmailSubjectField(): void
    {
        $value = get_option(Config::PLUGIN_ABBREV . '_email_subject', '');
        echo '<input type="text" name="'.Config::PLUGIN_ABBREV.'_email_subject" value="' . esc_attr($value) . '" style="width:50%;">';
    }

    /**
     * Render Contact Fields UI
     * 
     * @return void
     */
    public function renderContactFields(): void
    {
        $fields = get_option(Config::PLUGIN_ABBREV . '_contact_fields', []);
        if (!is_array($fields)) {
            $fields = [];
        }
        ?>
        <style>
            .contact-field-options { margin-top: 12px; }
            .contact-field-group {
                padding: 6px;
                border: 1px #2271b1 solid;
                border-radius: 4px;
                width: fit-content;
                margin-bottom: 12px;
            }
            .contact-fields-wrapper .contact-field-group:not(:first-child) {
                margin-top: 12px;
            }
            .contact-field-option, .add-option { margin-top: 6px !important; }
        </style>

        <div id="contact-fields-wrapper">
            <?php foreach ($fields as $index => $field): ?>
                <div class="contact-field-group">
                    <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][name]" value="<?= esc_attr($field['name'] ?? '') ?>" placeholder="Field Name">
                    <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][placeholder]" value="<?= esc_attr($field['placeholder'] ?? '') ?>" placeholder="Placeholder">
                    <select name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][type]" class="contact-field-type">
                        <option disabled <?= empty($field['type']) ? 'selected' : '' ?>>Select A Field Type</option>
                        <?php
                        $types = ['text','email','tel','textarea','checkbox','select','radio'];
                        foreach ($types as $type) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($type),
                                selected($field['type'] ?? '', $type, false),
                                ucfirst($type)
                            );
                        }
                        ?>
                    </select>
                    <label>
                        <input type="checkbox" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][required]" value="1" <?= checked($field['required'] ?? false, true, false) ?>> Required
                    </label>
                    <button type="button" class="button remove-contact-field">Remove</button>

                    <!-- Options for select/radio/checkbox -->
                    <div class="contact-field-options" style="<?= in_array($field['type'] ?? '', ['select','radio','checkbox'], true) ? '' : 'display:none;' ?>">
                        <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                            <?php foreach ($field['options'] as $oIndex => $opt): ?>
                                <div class="contact-field-option">
                                    <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][options][<?= $oIndex ?>][label]" value="<?= esc_attr($opt['label'] ?? '') ?>" placeholder="Option Label">
                                    <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[<?= $index ?>][options][<?= $oIndex ?>][value]" value="<?= esc_attr($opt['value'] ?? '') ?>" placeholder="Option Value">
                                    <button type="button" class="button remove-option">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button type="button" class="button add-option">Add Option</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button" id="add-contact-field">Add Field</button>

        <script>
        (function(){
            document.addEventListener('DOMContentLoaded', function() {
                const wrapper = document.getElementById('contact-fields-wrapper');
                const addBtn = document.getElementById('add-contact-field');

                // Add field (prevent duplicate blank)
                addBtn.addEventListener('click', function() {
                    const groups = wrapper.querySelectorAll('.contact-field-group');
                    for (let g of groups) {
                        const nameVal = g.querySelector('input[name*="[name]"]').value.trim();
                        const typeVal = g.querySelector('.contact-field-type').value;
                        if (!nameVal || !typeVal || typeVal === "Select A Field Type") {
                            alert("Please fill in the blank field before adding another.");
                            return;
                        }
                    }

                    const index = groups.length;
                    const group = document.createElement('div');
                    group.classList.add('contact-field-group');
                    group.innerHTML = `
                        <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${index}][name]" placeholder="Field Name">
                        <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${index}][placeholder]" placeholder="Placeholder">
                        <select name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${index}][type]" class="contact-field-type">
                            <option disabled selected>Select A Field Type</option>
                            <option value="text">Text</option>
                            <option value="email">Email</option>
                            <option value="tel">Tel</option>
                            <option value="textarea">Textarea</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="select">Select</option>
                            <option value="radio">Radio</option>
                        </select>
                        <label><input type="checkbox" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${index}][required]" value="1"> Required</label>
                        <button type="button" class="button remove-contact-field">Remove</button>
                        <div class="contact-field-options" style="display:none;">
                            <button type="button" class="button add-option">Add Option</button>
                        </div>
                    `;
                    wrapper.appendChild(group);
                });

                // Remove field
                wrapper.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-contact-field')) {
                        e.target.closest('.contact-field-group').remove();
                    }
                });

                // Toggle options on type change
                wrapper.addEventListener('change', function(e) {
                    if (e.target.classList.contains('contact-field-type')) {
                        const type = e.target.value;
                        const optionsDiv = e.target.closest('.contact-field-group').querySelector('.contact-field-options');
                        optionsDiv.style.display = (['select','radio','checkbox'].includes(type)) ? '' : 'none';
                    }
                });

                // Add option
                wrapper.addEventListener('click', function(e) {
                    if (e.target.classList.contains('add-option')) {
                        const fieldGroup = e.target.closest('.contact-field-group');
                        const optionsDiv = fieldGroup.querySelector('.contact-field-options');
                        const fieldIndex = Array.from(wrapper.children).indexOf(fieldGroup);
                        const optionIndex = optionsDiv.querySelectorAll('.contact-field-option').length;

                        const optionDiv = document.createElement('div');
                        optionDiv.classList.add('contact-field-option');
                        optionDiv.innerHTML = `
                            <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${fieldIndex}][options][${optionIndex}][label]" placeholder="Option Label">
                            <input type="text" name="<?= Config::PLUGIN_ABBREV ?>_contact_fields[${fieldIndex}][options][${optionIndex}][value]" placeholder="Option Value">
                            <button type="button" class="button remove-option">Remove</button>
                        `;
                        e.target.before(optionDiv);
                    }
                });

                // Remove option
                wrapper.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-option')) {
                        e.target.closest('.contact-field-option').remove();
                    }
                });
            });
        })();
        </script>
        <?php
    }
}