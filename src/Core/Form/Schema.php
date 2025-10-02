<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

use QuizScoringForms\Core\Form\Field as FormField;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

class Schema {

    private array $fields = [];

    public function getFields(): array {
        return $this->fields;
    }

    public function appendField(array $section, array $question, int $order) {
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
}