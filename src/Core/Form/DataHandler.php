<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

use QuizScoringForms\Core\Form\Schema;

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
     * Array of sections in the quiz.
     *
     * @var array
     */
    public readonly array $sections;

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
    public Schema $fieldsSchema;

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
        $this->sections = $postData['sections'];
        $this->answers = $postData['answers'];
        $this->results = $postData['results'];
        $this->fieldsSchema = new Schema();

        $this->appendQuestions();
        var_dump(json_encode($this->fieldsSchema->getFields()));
    }

    /**
     * Append questions from each section to the schema.
     * 
     * @return void
     */
    private function appendQuestions():void {
        $this->fieldsSchema = new Schema();
        $questionOrderCounter = 1;
        foreach($this->sections as $section) {
            foreach($section['questions'] as $index => $question) {
                $this->fieldsSchema->appendField($section, $question, $questionOrderCounter);
                $questionOrderCounter++;
            }
        }
    }

    /**
     * Append schema data to the existing schema.
     * @param array $schema
     * @return void
     */
    public function appendSchema(array $schema):void {

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
    public function getSchema(): array {
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

    /**
     * Get the schema used to validate results data.
     * 
     * @return array
     */

    public function getResultsSchema(): array
    {
        /**
         * The schema used to validate results data.
         * 
         * @var array
         */

        if (!empty($this->resultsSchema)) {
            return $this->resultsSchema;
        }

        // If the data is empty, fetch it
        $this->resultsSchema = $this->getData()['results'];
        
        return $this->resultsSchema;
    }

    /**
     * Get the name of the quiz.
     * 
     * @return string
     */
    public function getQuizName() {
        if (empty($this->data['title'])) {
            $$this->getData();
        }
        if (empty($this->data['title'])) {
            throw new \Exception('Unable to fetch data to get name of quiz.');
        }
        return $this->data['title'];
    }
}