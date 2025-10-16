<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Answer
 *
 * Represents a form answer with associated ID, text, and value.
 *
 * This class is a simple data container for form answers and their associated metadata.
 *
 * @package QuizScoringForms\Core\Form
 */
final class Answer
{
    /**
     * The field data for the section.
     *
     * An associative array containing the field data.
     *
     * @see setFieldsData()
     *
     * @var array
     */
    public readonly array $fieldsData;

    /**
     * Constructor
     *
     * @param string $id The ID of the answer.
     * @param string $text The text of the answer.
     * @param string $value The value of the answer.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $text,
        public readonly string $value,
    ) {}
}