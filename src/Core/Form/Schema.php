<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

use QuizScoringForms\Core\Form\Field as FormField;
use QuizScoringForms\Core\Form\Section as FormSection;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Schema
 *
 * Represents a form schema that contains questions and sections.
 */
class Schema {

    /**
     * Array of FormField objects that represent the questions in the form.
     * @var array<FormField>
     */
    private array $fields = [];

    /**
     * Array of FormSection objects that represent the sections in the form.
     * @var array<FormSection>
     */
    private array $sections = [];

    /**
     * Adds a FormField object to the schema.
     * 
     * @param array $section The section object containing the ID, title, slug, and order.
     * @param array $question The question object containing the ID, text, and order.
     * @param int $order The order of the question in the section.
     */
    public function addQuestionField(array $section, array $question, int $order): void {
        $this->fields[] = new FormField(
            $question['id'],
            $section['id'],
            $order,
            'radio',
            $question['text'],
            $question['text'],
            'string',
            1,
            10,
            'Please answer the question',
            true,
            true
        );
    }

    /**
     * Adds a FormSection object to the schema.
     * 
     * @param array $section The section object containing the ID, title, slug, and order.
     * @param int $order The order of the section.
     * @param array $questionIds The IDs of the questions in the section.
     */
    public function addSection(array $section, int $order, array $questionIds): void {
        $this->sections[] = new FormSection(
            $section['id'],
            $section['title'],
            $section['slug'],
            $order,
            $questionIds
        );
    }

    /**
     * Returns the array of FormField objects.
     * 
     * @return array<FormField>
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * Returns the array of FormSection objects.
     * 
     * @return array<FormSection>
     */
    public function getSections(): array {
        return $this->sections;
    }
}
