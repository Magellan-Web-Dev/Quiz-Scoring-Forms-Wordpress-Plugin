<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form\Schema;

use QuizScoringForms\Config;
use QuizScoringForms\Core\Form\Schema\Field as FormField;
use QuizScoringForms\Core\Form\Schema\Section as FormSection;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Initializer
 *
 * Represents a form schema that contains questions and sections.
 */
class Initializer 
{

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
     * Adds a FormSection object to the schema.
     * 
     * @param array $section The section object containing the ID, title, slug, and order.
     * @param int $order The order of the section.
     * @param array $questionIds The IDs of the questions in the section.
     */
    public function addSection(array $section, int $order, array $questionIds, bool $isQuestion): void 
    {
        $this->sections[] = new FormSection(
            $this->setIdNamespace($section['id']),
            $section['title'],
            $section['slug'],
            $order,
            array_map(fn($id) => $this->setIdNamespace($id), $questionIds),
            $isQuestion
        );
    }

    /** 
     * Adds a FormField object to the schema.
     * 
     * @param array $contactSection The contact section object containing the ID.
     * @param array $contact The contact field object containing the ID, type, name, placeholder, and required.
     * @param int $order The order of the contact field.
     * 
     * @return void
     * 
    */
    public function addContactField(array $contactSection, array $contact, int $order): void 
    {
        $dataType = match($contact['type']) {
            'text' => 'string',
            'email' => 'email',
            'tel' => 'phone',
            'number' => 'int',
            default => 'string',
        };

        $this->fields[] = new FormField(
            $this->setIdNamespace($contact['id']),
            $contactSection['id'],
            $order,
            $contact['type'],
            $contact['name'],
            $contact['placeholder'],
            $dataType,
            2,
            50,
            'Please enter your ' . strtolower($contact['name']),
            $contact['required'],
            false
        );
    }

    /**
     * Adds a FormField object to the schema.
     * 
     * @param array $section The section object containing the ID, title, slug, and order.
     * @param array $question The question object containing the ID, text, and order.
     * @param int $order The order of the question in the section.
     * 
     * @return void
     */
    public function addQuestionField(array $section, array $question, int $order): void 
    {
        $this->fields[] = new FormField(
            $this->setIdNamespace($question['id']),
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
     * Sets the ID namespace for a given ID.  This helps prevent HTML ID conflicts.
     * 
     * @param string $id The ID to set the namespace for.
     * @return string The ID with the namespace.
     */
    private function setIdNamespace($id) 
    {
        return Config::PLUGIN_ABBREV . '_' . $id;
    }

    /**
     * Returns the array of FormField objects.
     * 
     * @return array<FormField>
     */
    public function getAllFields(): array 
    {
        return $this->fields;
    }

    /**
     * Returns the array of FormSection objects.
     * 
     * @return array<FormSection>
     */
    public function getAllSections(): array 
    {
        return $this->sections;
    }

    /**
     * Returns the array of FormField objects that are not questions, sorted in order.
     * 
     * @return array<FormField>
     */
    public function getAllContactFields(): array 
    {
        $contactFields = array_filter(
            $this->fields, 
            fn($field) => !$field->isQuestion
        );

        usort($contactFields, function($a, $b) {
            return $a->order <=> $b->order; 
        });

        return $contactFields;
    }

    /**
     * Returns the array of FormSection objects that are questions, sorted in order.
     * 
     * @return array<FormSection>
     */
    public function getAllQuestionsSections(): array 
    {
        $questionSections = array_filter(
            $this->sections, 
            fn($section) => $section->questionSection
        );

        usort($questionSections, function($a, $b) {
            return $a->order <=> $b->order;
        });
        $questionSections = array_map(function($section) {
            $fields = [];
            foreach ($section->fields as $sectionField) {
                $correspondingField = array_filter($this->fields, fn($field) => $field->id === $sectionField);
                array_push($fields, $correspondingField);
            }
            $section->setFieldsData($fields);
            return $section;
        }, $questionSections);
        return $questionSections;
    }
}
