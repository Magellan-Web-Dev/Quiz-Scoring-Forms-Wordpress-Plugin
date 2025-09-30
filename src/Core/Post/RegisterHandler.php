<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Post;

use QuizScoringForms\Config;
use QuizScoringForms\UI\Dashboard\PostMetaBox as MetaBoxUI;
use QuizScoringForms\Core\API\Post\Router as APIRouter;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class RegisterHandler
 *
 * Handles all responsibilities related to registering and managing
 * the custom Quiz post type in WordPress.
 *
 * Responsibilities:
 * - Register the custom post type.
 * - Register the admin meta box for managing quiz sections.
 * - Save and sanitize quiz meta box input.
 * - Register quiz meta fields for the REST API.
 * - Register REST API routes through the {@see APIRouter} class for accessing quiz data.
 *
 * This class acts as the "primary handler" for post type lifecycle
 * and delegates UI responsibilities to the {@see MetaBoxUI} class
 * and API request logic to the {@see APIController} class.
 */
final class RegisterHandler
{
    /**
     * The post type name (configured in Config).
     *
     * @var string
     */
    private string $postType;

    /**
     * The meta key used to store quiz sections.
     *
     * @var string
     */
    private string $metaKey;

    /**
     * The nonce action string for verifying meta box saves.
     *
     * @var string
     */
    private string $nonceAction;

    /**
     * The nonce name field for meta box security.
     *
     * @var string
     */
    private string $nonceName;

    /**
     * The API router for handling REST API requests.
     *
     * @var APIRouter
     */
    private APIRouter $apiRouter;

    /**
     * Constructor.
     * 
     * Initializes variables from {@see Config} and attaches
     * all necessary WordPress hooks.
     */
    public function __construct()
    {
        $this->postType    = Config::POST_TYPE;
        $this->metaKey     = '_' . $this->postType . '_sections';
        $this->nonceAction = $this->postType . '_meta_box';
        $this->nonceName   = $this->postType . '_meta_box_nonce';
        $this->apiRouter   = new APIRouter(Config::SLUG, strtolower(Config::POST_NAME)); // Register API routes

        // Hook into WordPress
        add_action('init', [$this, 'registerPostType']); // Register CPT
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']); // Register UI meta box
        add_action('init', [$this, 'registerMetaFields']); // Register meta fields
        add_action("save_post_{$this->postType}", [$this, 'saveMetaBoxData'], 10, 2); // Save handler
    }

    /**
     * Register the custom post type with WordPress.
     *
     * Defines labels, arguments, and capabilities for the post type.
     *
     * @return void
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
            'supports'    => ['title'],   // Only the title is supported by default
            'show_in_rest'=> false,       // We manage REST manually with custom routes
        ];

        register_post_type($this->postType, $args);
    }

    /**
     * Register the meta box for quiz sections in the admin editor screen.
     *
     * Attaches the {@see MetaBoxUI} renderer class which outputs the HTML, CSS, and JS.
     *
     * @return void
     */
    public function registerMetaBoxes(): void
    {
        add_meta_box(
            $this->postType . '_sections', // ID of meta box
            Config::POST_NAME . ' Sections', // Title shown in editor
            function(\WP_Post $post) {
                // Delegate rendering to UI class
                (new MetaBoxUI(
                    $this->postType,
                    $this->metaKey,
                    $this->nonceAction,
                    $this->nonceName
                ))->render($post);
            },
            $this->postType, // Post type where box appears
            'normal',        // Context: main column
            'high'           // Priority: show near top
        );
    }

    /**
     * Register quiz sections as a structured post meta field.
     *
     * This ensures quiz "sections" can be exposed in the REST API,
     * validated, and sanitized consistently.
     *
     * @return void
     */
    public function registerMetaFields(): void
    {
        register_post_meta($this->postType, $this->metaKey, [
            'type'          => 'object',   // still an object with 'sections' and 'answers'
            'single'        => true,
            'show_in_rest'  => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'sections' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'        => ['type' => 'string'],
                                    'title'     => ['type' => 'string'],
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
                                'required' => ['id', 'title', 'questions'],
                            ],
                        ],
                        'answers' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'text'  => ['type' => 'string'],
                                    'value' => ['type' => 'string'],
                                ],
                                'required' => ['text', 'value'],
                            ],
                        ],
                    ],
                    'required' => ['sections', 'answers'],
                ],
            ],
            'sanitize_callback' => fn($value) => $this->sanitizeQuizData((array)$value),
            'auth_callback'     => fn() => current_user_can('edit_posts'),
        ]);
    }


    /**
     * Save the structured sections/questions meta box data when the post is saved.
     *
     * Performs the following checks before saving:
     * - Nonce verification
     * - Autosave detection
     * - Post type check
     * - User capability check
     *
     * @param int $postId The ID of the post being saved.
     * @param \WP_Post $post The post object being saved.
     * @return void
     */
    public function saveMetaBoxData(int $postId, \WP_Post $post): void
    {
        // Verify nonce
        if (
            !isset($_POST[$this->nonceName]) ||
            !wp_verify_nonce($_POST[$this->nonceName], $this->nonceAction)
        ) {
            return;
        }

        // Skip if autosave is running
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Only handle our custom post type
        if ($post->post_type !== $this->postType) {
            return;
        }

        // Check user capability
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // Data
        $data = $_POST[$this->postType . '_data'] ?? [];

        // Sanitize both
        $sanitized = $this->sanitizeQuizData([
            'sections' => $data['sections'] ?? [],
            'answers'  => $data['answers'] ?? [],
        ]);
        
        update_post_meta($postId, $this->metaKey, $sanitized);
    }

    /**
     * Sanitize quiz data.
     * 
     * Performs the following checks:
     * - Sections
     * - Answers
     * 
     * @param array $data Raw data to sanitize
     * @return array Sanitized data
     */
    private function sanitizeQuizData(array $data): array
    {
        $sanitized = [
            'sections' => [],
            'answers'  => [],
        ];

        // --- Sections ---
        $sections = $data['sections'] ?? [];
        foreach ($sections as $sectionIndex => $section) {
            $sectionId = 's' . ($sectionIndex + 1);
            $id    = sanitize_text_field($section['id'] ?? $sectionId);
            $title = sanitize_text_field($section['title'] ?? '');

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
                'questions' => $questions,
            ];
        }

        // --- Answers ---
        $answers = $data['answers'] ?? [];
        foreach ($answers as $answer) {
            $text  = sanitize_text_field($answer['text'] ?? '');
            $value = sanitize_text_field($answer['value'] ?? '');
            if ($text !== '' && $value !== '') {
                $sanitized['answers'][] = [
                    'text'  => $text,
                    'value' => $value,
                ];
            }
        }

        return $sanitized;
    }


    /**
     * Centralized sanitizer for quiz sections/questions.
     *
     * Ensures both REST API input and meta box input
     * are treated consistently and safely.
     *
     * @param array $sections Raw input array of sections
     * @return array Sanitized array of sections
     */
    private function sanitizeSections(array $sections): array
    {
        $sanitized = [];

        foreach ($sections as $sectionIndex => $section) {
            $sectionId = 's' . ($sectionIndex + 1);
            $id    = sanitize_text_field($section['id'] ?? $sectionId);
            $title = sanitize_text_field($section['title'] ?? '');

            // Normalize questions: could come as string (from textarea) or array (from API)
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

            $sanitized[] = [
                'id'        => $id,
                'title'     => $title,
                'questions' => $questions,
            ];
        }

        return $sanitized;
    }
}