<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form;

use QuizScoringForms\Core\Form\Field as FormField;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class to handle fetching and validation of form data.
 */

final class DataHandler {

    /**
     * The fetched data.
     * @var array
     */
    private array $data = [];

    /**
     * The schema used to validate form data.
     * @var array
     */
    private array $schema = [];

    /**
     * The schema used to validate results data.
     * @var array
     */
    private array $resultsSchema = [];

    /**
     * The schema used to validate scoring data.
     * @var array
     */
    private array $scoringSchema = [];

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Get the fetched data.
     * @return array
     * @throws \Exception
     */
    public function getData() {

        // If the data is already available, return it
        if (!empty($this->data)) {
            return $this->data;
        }

        // If the data url query or data url is empty, throw an exception
        if (!defined('QUIZ_QUERY') || QUIZ_QUERY === '') {
            throw new \Exception('Invalid or empty data url query provided.');
        }

        // If the data url is empty, throw an exception
        if (!defined('DATA_URL') || DATA_URL === '') {
            throw new \Exception('Invalid or empty data url provided.');
        }

        // Fetch the data
        $fetchData = @file_get_contents(DATA_URL);
        
        // If the data could not be fetched, throw an exception
        if ($fetchData === false) {
            throw new \Exception('Unable to fetch data from url: ' . DATA_URL);
        }

        // Parse the data
        $fetchData = json_decode($fetchData, true);

        // If the data could not be parsed, throw an exception
        if ($fetchData === false) {
            throw new \Exception('Unable to parse data from url: ' . DATA_URL);
        }

        // Find the data for the url query
        $quizData = false;

        // Loop through the data to find the data for the url query
        foreach($fetchData as $quiz) {
            if ($quiz['slug'] === QUIZ_QUERY) {
                $quizData = $quiz;
                break;
            }
        }

        // Get the answer values for validation
        $answerValues = array_values($quizData['scoring']);
        $answerValues = array_map('strval', $answerValues);

        // Concatenate the section id to the question id to ensure every question has a unique id and set schema
        foreach($quizData['sections'] as $sectionIndex => $sectionData) {
            foreach($sectionData['questions'] as $questionIndex => $questionData) {
                $questionData['id'] = $sectionData['id'] . '-' . $questionData['id'];
                $formField = new FormField(
                    $questionData['id'],
                    $sectionData['id'],
                    $questionData['text'],
                    'text',
                    Validator::in($answerValues),
                    'Please select an answer.',
                    true,
                    true
                );
                $questionData['field'] = $formField;
                $quizData['sections'][$sectionIndex]['questions'][$questionIndex] = $questionData;
                $this->schema['fields'][$questionData['id']] = $formField;
            }
        }

        // If the data could not be found, throw an exception
        if ($quizData === false) {
            throw new \Exception('Unable to find data for url query: ' . QUIZ_QUERY);
        }

        // Set the data
        $this->data = $quizData;

        // Set the sections in the schema
        foreach($this->data['sections'] as $section) {
            $this->schema['sections'][$section['id']] = $section['title'];
        }

        return $this->data;
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