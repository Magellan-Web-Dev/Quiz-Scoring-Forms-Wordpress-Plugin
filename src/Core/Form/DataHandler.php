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
     * Array of contact sectionfields in the quiz.
     * 
     * @var array
     */
    public readonly array $contactSection;

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
    public function __construct(array $postData) 
    {
        $this->title = $postData['title'];
        $this->description = $postData['description'];
        $this->instructions = $postData['instructions'];
        $this->contactSection = $postData['contactSection'];
        $this->questionSections = $postData['questionSections'];
        $this->answers = $postData['answers'];
        $this->results = $postData['results'];
        $this->schema = new Schema();

        $this->setContactsData();
        $this->setQuestionsData();
    }

    /**
     * Set the contacts data. Sets the schema for all the contact fields.
     * 
     * @return void
     */
    public function setContactsData(): void 
    {
        $contactSectionData = [
            'id' => 'contacts',
            'title' => 'Contacts',
            'slug' => 'contacts',
        ];
        $this->schema->addSection($contactSectionData, 0, $this->getContactSectionIds(), false);
        foreach($this->contactSection as $contactIndex => $contactField) {
            $this->schema->addContactField($contactSectionData, $contactField, ($contactIndex + 1));
        }
    }

    /**
     * Set the questions data. Sets the schema for all the question fields and the corresponding sections each question is in.
     * 
     * @return void
     */
    private function setQuestionsData():void 
    {
        foreach($this->questionSections as $sectionIndex => $questionSection) {
            $this->schema->addSection($questionSection, ($sectionIndex + 1), $this->getSectionQuestionsIds($questionSection), true);
            foreach($questionSection['questions'] as $questionIndex => $question) {
                $this->schema->addQuestionField($questionSection, $question, ($questionIndex + 1));
            }
        }
    }

    /**
     * Get the ids of the contact fields.
     * 
     * @return array
     */
    private function getContactSectionIds(): array 
    {
        $contactIds = [];
        foreach($this->contactSection as $contactField) {
            $contactIds[] = $contactField['id'];
        }
        return $contactIds;
    }

    /**
     * Get the ids of the questions in a section.
     * 
     * @param array $section
     * @return array
     */
    private function getSectionQuestionsIds(array $section): array 
    {
        $questionIds = [];
        foreach($section['questions'] as $question) {
            $questionIds[] = $question['id'];
        }
        return $questionIds;
    }

    /**
     * Append schema data to the existing schema.
     * @param array $schema
     * @return void
     */
    public function addContactFields(array $schema):void 
    {
        // Schema validation
        $revisedSchema = [];

        // Loop through the schema and set each from the form field schema
        foreach($schema as $s) {
            $this->schema['fields'][$s->get('id')] = $s;
        }
    }
}