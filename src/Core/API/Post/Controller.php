<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\API\Post;

use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Controller
 *
 * Handles REST API requests related to quiz post data
 * 
 * Responsibilities:
 * - Fetch collections of quizzes with pagination and search.
 * - Fetch a single quiz by ID.
 * - Format quiz data into a consistent JSON structure.
 * - Provide parameter definitions for the quiz collection endpoint.
 *
 * NOTE:
 * This class does not handle REST route registration. That is done
 * in the {@see QuizScoringForms\Core\API\Post\Router} class. Here we only respond to requests.
 */
final class Controller
{
    /**
     * Handle a REST request to retrieve a collection of quizzes.
     *
     * Example Request:
     *   GET /quizscoringforms/v1/quizzes?per_page=5&page=2&search=math
     *
     * @param \WP_REST_Request $request The REST API request object.
     * @return \WP_REST_Response JSON response with quizzes and pagination info.
     */
    public function getQuizzes(\WP_REST_Request $request): \WP_REST_Response
    {
        // Build query args for WP_Query using request parameters
        $args = [
            'post_type'      => Config::POST_TYPE,
            'posts_per_page' => $request->get_param('per_page') ?? get_option('posts_per_page'),
            'paged'          => $request->get_param('page') ?? 1,
            's'              => $request->get_param('search') ?? '',
        ];

        // Execute query for quiz posts
        $query = new \WP_Query($args);
        $items = [];

        // Prepare each quiz post for JSON response
        foreach ($query->posts as $post) {
            $items[] = $this->prepareQuizData($post);
        }

        // Return paginated quiz collection
        return new \WP_REST_Response([
            'total'      => (int) $query->found_posts,     // total number of matched posts
            'per_page'   => (int) $args['posts_per_page'], // items per page
            'page'       => (int) $args['paged'],          // current page number
            'totalPages' => (int) $query->max_num_pages,   // total pages available
            'items'      => $items,                        // prepared quiz data
        ], 200);
    }

    /**
     * Handle a REST request to retrieve a single quiz by ID.
     *
     * Example Request:
     *   GET /quizscoringforms/v1/quizzes/123
     *
     * @param \WP_REST_Request $request The REST API request object.
     * @return \WP_REST_Response JSON response with quiz data or 404 if not found.
     */
    public function getQuiz(\WP_REST_Request $request): \WP_REST_Response
    {
        $id   = (int) $request['id'];     // cast to int for safety
        $post = get_post($id);

        // Return 404 if post does not exist or is wrong type
        if (!$post || $post->post_type !== Config::POST_TYPE) {
            return new \WP_REST_Response([
                'message' => Config::POST_NAME_SINGULAR . ' not found',
            ], 404);
        }

        // Return formatted quiz data
        return new \WP_REST_Response($this->prepareQuizData($post), 200);
    }

    /**
     * Prepare a quiz post object for JSON output.
     *
     * Collects quiz meta fields (like sections) and standard WP post data,
     * then builds a clean, consistent JSON structure for API responses.
     *
     * @param \WP_Post $post The WordPress post object representing a quiz.
     * @return array JSON-serializable associative array of quiz data.
     */
    private function prepareQuizData(\WP_Post $post): array
    {
        $meta = get_post_meta($post->ID, '_' . Config::POST_TYPE . '_sections', true);

        $sections = $meta['sections'] ?? [];
        $answers  = $meta['answers'] ?? [];

        return [
            'id'       => $post->ID,
            'title'    => get_the_title($post),
            'slug'     => $post->post_name,
            'date'     => get_the_date('', $post),
            'modified' => get_the_modified_date('', $post),
            'sections' => $sections,
            'answers'  => $answers,
        ];
    }

    /**
     * Define accepted collection query parameters.
     *
     * These parameters mirror the format used in the WordPress core 
     * `/wp/v2/posts` endpoint, allowing consistent query handling.
     *
     * @return array List of accepted query parameters with schema definitions.
     */
    public function getCollectionParams(): array
    {
        return [
            'per_page' => [
                'description'       => 'Number of items per page',
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint', // force int
            ],
            'page' => [
                'description'       => 'Page of results to return',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'description'       => 'Limit results to those matching a search string',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field', // strip unsafe chars
            ],
        ];
    }
}