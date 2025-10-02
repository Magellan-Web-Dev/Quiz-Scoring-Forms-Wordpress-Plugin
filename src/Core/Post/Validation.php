<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Post;

use QuizScoringForms\Config;
use QuizScoringForms\Services;
use QuizScoringForms\Services\ErrorGenerator as Errors;
use QuizScoringForms\Services\TransientStorage as Storage;

/**
 * Prevent direct access from outside WordPress
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Validation
 *
 * Responsible for sanitizing and validating quiz data.
 * 
 * @package QuizScoringForms
 */
final class Validation
{
    public readonly string $nonceAction;
    public readonly string $nonceName;
    public readonly string $storageKey;
    public readonly Storage $storedData;
    public readonly Storage $storedErrors;

    public function __construct($nonceAction, $nonceName, $storageKey) {
        $this->nonceAction = $nonceAction;
        $this->nonceName   = $nonceName;
        $this->storageKey  = $storageKey;
        $this->storedData  = new Storage($this->storageKey . '_validation_data', 30);
        $this->storedErrors = new Storage($this->storageKey . '_validation_errors', 30);
    }
    /**
     * Validate post data before WordPress saves it.
     *
     * Forces invalid Quiz posts to 'draft' status to prevent blank fields in the editor.
     * Stores validation errors and raw data in transients for repopulation and admin notices.
     *
     * @param array $data    The post data being saved
     * @param array $postarr The original post array
     * @return array Modified post data
     */
    public function validatePostBeforeSave(array $data, array $postarr): array
    {
        // Only validate our custom post type
        if (($postarr['post_type'] ?? '') !== Config::POST_TYPE) {
            return $data;
        }

        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $data;
        }

        // Skip if nonce is missing
        if (!isset($_POST[$this->nonceName]) || !wp_verify_nonce(sanitize_text_field($_POST[$this->nonceName]), $this->nonceAction)) {
            return $data;
        }

        // Collect raw submitted data
        $rawData = $_POST[Config::POST_TYPE . '_data'] ?? [];

        $structured = [
            'description' => $rawData['description'] ?? '',
            'instructions'=> $rawData['instructions'] ?? '',
            'sections'    => $rawData['sections'] ?? [],
            'answers'     => $rawData['answers'] ?? [],
            'results'     => $rawData['results'] ?? [],
        ];

        $sanitized = $this->sanitizeQuizData($structured);
        $errors    = self::validateQuizData($sanitized);

        if (!empty($errors)) {
            // Force the post to draft
            $data['post_status'] = 'draft';

            // Store validation errors for admin_notices
            $this->storedErrors->set($errors, $postarr['ID']);

            // Store raw submitted data for repopulation
            $this->storedData->set($structured, $postarr['ID']);
        }

        return $data;
    }

    /**
     * Validate quiz data against business rules.
     *
     * @param array $data Sanitized quiz data
     * @return array Array of error messages (empty if valid)
     */
    public function validateQuizData(array $data): array
    {
        $errors = [];

        // Description required
        if ($data['description'] === '') {
            $errors[] = 'Description is required.';
        }

        // Instructions required
        if ($data['instructions'] === '') {
            $errors[] = 'Instructions are required.';
        }

        // At least one section with one question
        if (empty($data['sections'])) {
            $errors[] = 'At least one section with a question is required.';
        } else {
            $hasQuestion = false;
            foreach ($data['sections'] as $section) {
                if (!empty($section['questions'])) {
                    $hasQuestion = true;
                    break;
                }
            }
            if (!$hasQuestion) {
                $errors[] = 'At least one question is required in a section.';
            }
        }

        // At least one answer
        if (empty($data['answers'])) {
            $errors[] = 'At least one answer option is required.';
        }

        // At least one result
        if (empty($data['results'])) {
            $errors[] = 'At least one result is required.';
        }

        return $errors;
    }

    /**
     * Display validation errors stored in transients as admin notices.
     */
    public function displayValidationErrors(): void
    {
        global $post;

        if (!$post) {
            return;
        }

        $errors = $this->storedErrors->get($post->ID);
        if ($errors) {
            $this->storedErrors->delete($post->ID);

            foreach ($errors as $error) {
                echo Errors::errorHTMLMsg($error);
            }
        }
    }

    /**
     * Sanitize quiz data (description, instructions, sections, answers, results).
     *
     * Ensures consistent structure, safe values, and auto-generated IDs
     * for sections, questions, answers, and results.
     *
     * @param array $data Raw submitted quiz data from the metabox or REST API
     *
     * @return array Sanitized quiz data ready for storage
     */
    public function sanitizeQuizData(array $data): array
    {
        // Initialize sanitized structure with defaults
        $sanitized = [
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'instructions'=> sanitize_textarea_field($data['instructions'] ?? ''),
            'sections'    => [],
            'answers'     => [],
            'results'     => [],
        ];

        /**
         * ---------------------
         * Sanitize Sections
         * ---------------------
         * Each section gets:
         * - id (auto: s1, s2… if not provided)
         * - title
         * - slug (generated from title)
         * - questions array with IDs (s1-q1, s1-q2…)
         */
        $sections = $data['sections'] ?? [];
        foreach ($sections as $sectionIndex => $section) {
            $sectionId = 's' . ($sectionIndex + 1);
            $id    = sanitize_text_field($section['id'] ?? $sectionId);
            $title = sanitize_text_field($section['title'] ?? '');

            // Handle questions inside section
            $questionsRaw = $section['questions'] ?? [];
            if (is_string($questionsRaw)) {
                $questionsRaw = explode("\n", $questionsRaw);
            }

            $questions = [];
            foreach ((array) $questionsRaw as $questionIndex => $q) {
                $questionId = $sectionId . '-q' . ($questionIndex + 1);
                $questions[] = [
                    'id'   => sanitize_text_field(is_array($q) && isset($q['id']) ? $q['id'] : $questionId),
                    'text' => sanitize_text_field(is_array($q) ? ($q['text'] ?? '') : $q),
                ];
            }

            $sanitized['sections'][] = [
                'id'        => $id,
                'title'     => $title,
                'slug'      => $this->generateSlug($title),
                'questions' => $questions,
            ];
        }

        /**
         * ---------------------
         * Sanitize Answers
         * ---------------------
         * Each answer gets:
         * - id (auto: a1, a2…)
         * - text
         * - value
         */
        $answers = $data['answers'] ?? [];
        foreach ($answers as $answerIndex => $answer) {
            $answerId = 'a' . ($answerIndex + 1);
            $text  = sanitize_text_field($answer['text'] ?? '');
            $value = sanitize_text_field($answer['value'] ?? '');

            if ($text !== '' && $value !== '') {
                $sanitized['answers'][] = [
                    'id'    => $answerId,
                    'text'  => $text,
                    'value' => $value,
                ];
            }
        }

        /**
         * ---------------------
         * Sanitize Results
         * ---------------------
         * Each result gets:
         * - id (auto: r1, r2…)
         * - title
         * - description
         * - min_percentage (float)
         * - max_percentage (float)
         */
        $results = $data['results'] ?? [];
        foreach ($results as $resultIndex => $result) {
            $resultId    = 'r' . ($resultIndex + 1);
            $title       = sanitize_text_field($result['title'] ?? '');
            $description = sanitize_textarea_field($result['description'] ?? '');
            $min         = isset($result['min_percentage']) ? floatval($result['min_percentage']) : null;
            $max         = isset($result['max_percentage']) ? floatval($result['max_percentage']) : null;

            if ($title !== '' && $description !== '' && $min !== null && $max !== null) {
                $sanitized['results'][] = [
                    'id'             => $resultId,
                    'title'          => $title,
                    'description'    => $description,
                    'min_percentage' => $min,
                    'max_percentage' => $max,
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Generate slug from title.
     * 
     * @param string $title
     * @return string
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}