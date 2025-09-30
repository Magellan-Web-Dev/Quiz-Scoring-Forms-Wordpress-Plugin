<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Post;

use QuizScoringForms\Config;
use QuizScoringForms\Core\Post\Validation;
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
    /**
     * The meta key for storing the quiz data in the post meta table.
     * @var string
     */
    private string $metaKey;

    /**
     * The nonce action name for the quiz meta box.
     * @var string
     */
    private string $nonceAction;

    /**
     * The nonce name for the quiz meta box.
     * @var string
     */
    private string $nonceName;

    /**
     * The router responsible for exposing the quiz data through the REST API.
     * @var APIRouter
     */
    private APIRouter $apiRouter;

    /**
     * The validation object responsible for validating the quiz data.
     * @var Validation
     */
    private Validation $validation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->metaKey     = '_' . Config::POST_TYPE . '_sections';
        $this->nonceAction = Config::POST_TYPE . '_meta_box';
        $this->nonceName   = Config::POST_TYPE . '_meta_box_nonce';
        $this->apiRouter   = new APIRouter(Config::SLUG, strtolower(Config::POST_NAME));
        $this->validation  = new Validation($this->nonceAction, $this->nonceName);

        // Register hooks
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('init', [$this, 'registerMetaFields']);
        add_action('save_post_' . Config::POST_TYPE, [$this, 'saveMetaBoxData'], 10, 2);
        add_action('admin_notices', [$this->validation, 'displayValidationErrors']);
        add_filter('wp_insert_post_data', [$this->validation, 'validatePostBeforeSave'], 10, 2);
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
            'sanitize_callback' => fn($value) => $this->validation->sanitizeQuizData((array)$value),
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
        $sanitized = $this->validation->sanitizeQuizData($structured);

        // --- Validate data ---
        $errors = $this->validation->validateQuizData($sanitized);

        if (!empty($errors)) {
            // Store validation errors for admin_notices
            $this->validation->setValidationErrors($postId, $errors);

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
}