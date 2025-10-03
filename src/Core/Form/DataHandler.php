<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

use QuizScoringForms\Core\Form\Schema\Initializer as Schema;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class to handle fetching and validation of form data.
 */

final class DataHandler {

    /**
     * Title of the quiz.
     *
     * @var string
     */
    public readonly string $title;

    /**
     * Description of the quiz.
     *
     * @var string
     */
    public readonly string $description;

    /**
     * Instructions for the quiz.
     *
     * @var string
     */
    public readonly string $instructions;

    /**
     * Array of questionsections in the quiz.
     *
     * @var array
     */
    public readonly array $questionSections;

    /**
     * Array of answers in the quiz.
     *
     * @var array
     */
    public readonly array $answers;

    /**
     * Array of results in the quiz.
     *
     * @var array
     */
    public readonly array $results;

    /**
     * Schema used to validate form fields data.
     * 
     * @var Schema
     */
    public Schema $schema;

    /**
     * Constructor.
     * 
     * @param array $postData
     * @return void
     */
    public function __construct(array $postData) {
        $this->title = $postData['title'];
        $this->description = $postData['description'];
        $this->instructions = $postData['instructions'];
        $this->questionSections = $postData['sections'];
        $this->answers = $postData['answers'];
        $this->results = $postData['results'];
        $this->schema = new Schema();

        $this->setQuestionsData();
        var_dump(json_encode($this->schema->getFields()));
    }

    /**
     * Set the questions data. Sets the schema for all the question fields and the corresponding sections each question is in.
     * 
     * @return void
     */

    private function setQuestionsData():void {
        $this->schema = new Schema();
        foreach($this->questionSections as $sectionIndex => $questionSection) {
            $this->schema->addSection($questionSection, ($sectionIndex + 1), $this->getSectionQuestionsIds($questionSection));
            foreach($questionSection['questions'] as $questionIndex => $question) {
                $this->schema->addQuestionField($questionSection, $question, ($questionIndex + 1));
            }
        }
    }

    /**
     * Get the ids of the questions in a section.
     * 
     * @param array $section
     * @return array
     */
    private function getSectionQuestionsIds(array $section): array {
        $questions = [];
        foreach($section['questions'] as $question) {
            $questions[] = $question['id'];
        }
        return $questions;
    }

    /**
     * Append schema data to the existing schema.
     * @param array $schema
     * @return void
     */
    public function addContactFields(array $schema):void {

        // Schema validation
        $revisedSchema = [];

        // Loop through the schema and set each from the form field schema
        foreach($schema as $s) {
            $this->schema['fields'][$s->get('id')] = $s;
        }
    }

    /**
     * Get the schema used to validate form data.
     * @return array
     */
    public function getSchema(): Schema {
        return $this->schema;
    }

    /**
     * Get the schema used to validate scoring data.
     *
     * @return array
     */
    public function getScoringSchema(): array
    {
        // If the scoring schema is not empty, return it
        if (!empty($this->scoringSchema)) {
            return $this->scoringSchema;
        }

        // If the data is empty, fetch it
        if (empty($this->data)) {
            $this->data = $this->getData();
            // If the data is still empty after fetching, throw an exception
            if (empty($this->data)) {
                throw new \Exception('Unable to fetch data for scoring schema.');
            }
        }

        // Set the scoring schema and return it
        $this->scoringSchema = $this->getData()['scoring'];
        return $this->scoringSchema;
    }
}