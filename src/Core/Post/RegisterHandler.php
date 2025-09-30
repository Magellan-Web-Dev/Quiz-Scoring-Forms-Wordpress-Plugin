<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Post;

use QuizScoringForms\Config;
use QuizScoringForms\UI\Dashboard\PostMetaBox as MetaBoxUI;
use QuizScoringForms\API\Post\Router as APIRouter;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class RegisterHandler
 *
 * Manages the lifecycle of the custom Quiz post type:
 * - Registers CPT
 * - Provides a custom meta box for quiz data
 * - Saves and sanitizes structured quiz data (description, instructions, sections, answers, results)
 * - Exposes data through REST API
 */
final class RegisterHandler
{
    private string $metaKey;
    private string $nonceAction;
    private string $nonceName;
    private APIRouter $apiRouter;

    public function __construct()
    {
        $this->metaKey     = '_' . Config::POST_TYPE . '_sections';
        $this->nonceAction = Config::POST_TYPE . '_meta_box';
        $this->nonceName   = Config::POST_TYPE . '_meta_box_nonce';
        $this->apiRouter   = new APIRouter(Config::SLUG, strtolower(Config::POST_NAME));

        // Register hooks
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('init', [$this, 'registerMetaFields']);
        add_action('save_post_' . Config::POST_TYPE, [$this, 'saveMetaBoxData'], 10, 2);
        add_action('admin_notices', [$this, 'displayValidationErrors']);
        add_filter('wp_insert_post_data', [$this, 'validatePostBeforeSave'], 10, 2);
    }

    /**
     * Register the custom post type.
     * 
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     * 
     */
    public function registerPostType(): void
    {
        $labels = [
            'name'               => Config::POST_NAME,
            'singular_name'      => Config::POST_NAME_SINGULAR,
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New ' . Config::POST_NAME_SINGULAR,
            'edit_item'          => 'Edit ' . Config::POST_NAME_SINGULAR,
            'new_item'           => 'New ' . Config::POST_NAME_SINGULAR,
            'view_item'          => 'View ' . Config::POST_NAME_SINGULAR,
            'search_items'       => 'Search ' . Config::POST_NAME,
            'not_found'          => 'No ' . strtolower(Config::POST_NAME) . ' found',
            'not_found_in_trash' => 'No ' . strtolower(Config::POST_NAME) . ' found in Trash',
            'all_items'          => 'All ' . Config::POST_NAME,
        ];

        $args = [
            'labels'      => $labels,
            'public'      => true,
            'menu_icon'   => 'dashicons-welcome-learn-more',
            'supports'    => ['title'],
            'show_in_rest'=> false, // Custom REST
        ];

        register_post_type(Config::POST_TYPE, $args);
    }

    /**
     * Register custom meta box for editing quiz data.
     * 
     * @see https://developer.wordpress.org/reference/functions/add_meta_box/
     */
    public function registerMetaBoxes(): void
    {
        add_meta_box(
            Config::POST_TYPE . '_sections',
            Config::POST_NAME . ' Settings',
            function(\WP_Post $post) {
                (new MetaBoxUI(
                    Config::POST_TYPE,
                    $this->metaKey,
                    $this->nonceAction,
                    $this->nonceName
                ))->render($post);
            },
            Config::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Register structured quiz meta fields for REST API.
     * 
     * @see https://developer.wordpress.org/reference/functions/register_post_meta/
     */
    public function registerMetaFields(): void
    {
        register_post_meta(Config::POST_TYPE, $this->metaKey, [
            'type'          => 'object',
            'single'        => true,
            'show_in_rest'  => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        // --- Global fields ---
                        'description' => ['type' => 'string'],
                        'instructions'=> ['type' => 'string'],

                        // --- Sections ---
                        'sections' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'        => ['type' => 'string'],
                                    'title'     => ['type' => 'string'],
                                    'slug'      => ['type' => 'string'],
                                    'questions' => [
                                        'type'  => 'array',
                                        'items' => [
                                            'type'       => 'object',
                                            'properties' => [
                                                'id'   => ['type' => 'string'],
                                                'text' => ['type' => 'string'],
                                            ],
                                            'required' => ['id', 'text'],
                                        ],
                                    ],
                                ],
                                'required' => ['id', 'title', 'slug', 'questions'],
                            ],
                        ],

                        // --- Answers ---
                        'answers' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'    => ['type' => 'string'],
                                    'text'  => ['type' => 'string'],
                                    'value' => ['type' => 'string'],
                                ],
                                'required' => ['id', 'text', 'value'],
                            ],
                        ],

                        // --- Results ---
                        'results' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'             => ['type' => 'string'],
                                    'title'          => ['type' => 'string'],
                                    'description'    => ['type' => 'string'],
                                    'min_percentage' => ['type' => 'number'],
                                    'max_percentage' => ['type' => 'number'],
                                ],
                                'required' => ['id', 'title', 'description', 'min_percentage', 'max_percentage'],
                            ],
                        ],
                    ],
                    'required' => ['sections', 'answers', 'results'],
                ],
            ],
            'sanitize_callback' => fn($value) => $this->sanitizeQuizData((array)$value),
            'auth_callback'     => fn() => current_user_can('edit_posts'),
        ]);
    }

    /**
     * Save quiz meta data on post save.
     *
     * Handles:
     * - Security checks (nonce, autosave, permissions)
     * - Sanitization and validation
     * - Saving valid data to post meta
     * - Storing invalid data + errors in transients for repopulation
     *
     * @param int      $postId The post ID.
     * @param \WP_Post $post   The post object.
     */
    public function saveMetaBoxData(int $postId, \WP_Post $post): void
    {
        // --- Security checks ---
        if (
            !isset($_POST[$this->nonceName]) ||
            !wp_verify_nonce(sanitize_text_field($_POST[$this->nonceName]), $this->nonceAction)
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== Config::POST_TYPE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // --- Collect raw submitted data ---
        $rawData = $_POST[Config::POST_TYPE . '_data'] ?? [];

        // Normalize into expected structure
        $structured = [
            'description' => $rawData['description'] ?? '',
            'instructions'=> $rawData['instructions'] ?? '',
            'sections'    => $rawData['sections'] ?? [],
            'answers'     => $rawData['answers'] ?? [],
            'results'     => $rawData['results'] ?? [],
        ];

        // --- Sanitize data ---
        $sanitized = $this->sanitizeQuizData($structured);

        // --- Validate data ---
        $errors = $this->validateQuizData($sanitized);

        if (!empty($errors)) {
            // Store validation errors for admin_notices
            $this->setValidationErrors($postId, $errors);

            // Store raw submitted data so the metabox can repopulate on reload
            set_transient("quiz_validation_data_$postId", $structured, 30);

            return; // stop here — don’t overwrite post meta with invalid data
        }

        // --- Save valid data ---
        update_post_meta($postId, $this->metaKey, $sanitized);

        // Clear any old validation remnants
        delete_transient("quiz_validation_data_$postId");
        delete_transient("quiz_validation_errors_$postId");
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
        $errors    = $this->validateQuizData($sanitized);

        if (!empty($errors)) {
            // Force the post to draft
            $data['post_status'] = 'draft';

            // Store validation errors for admin_notices
            $this->setValidationErrors((int)$postarr['ID'], $errors);

            // Store raw submitted data for repopulation
            set_transient("quiz_validation_data_{$postarr['ID']}", $structured, 30);
        }

        return $data;
    }

    /**
     * Validate quiz data against business rules.
     *
     * @param array $data Sanitized quiz data
     * @return array Array of error messages (empty if valid)
     */
    private function validateQuizData(array $data): array
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

        $errors = get_transient("quiz_validation_errors_{$post->ID}");
        if ($errors) {
            delete_transient("quiz_validation_errors_{$post->ID}");

            foreach ($errors as $error) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }

    /**
     * Store validation errors temporarily so they can be displayed as admin notices.
     *
     * @param int   $postId The post ID.
     * @param array $errors Array of error messages.
     */
    private function setValidationErrors(int $postId, array $errors): void
    {
        set_transient("quiz_validation_errors_$postId", $errors, 30);
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
    private function sanitizeQuizData(array $data): array
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