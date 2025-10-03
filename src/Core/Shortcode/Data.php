<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Shortcode;

use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;


/**
 * Class Data
 *
 * Represents the data that is returned by the shortcode when it is rendered.
 *
 * The constructor takes the following parameters:
 * - id: The ID of the post.
 * - title: The title of the post.
 * - slug: The slug of the post.
 * - date: The date of the post.
 * - modified: The modified date of the post.
 * - description: The description of the quiz.
 * - instructions: The instructions of the quiz.
 * - contacts: The contacts of the quiz.
 * - sections: The sections of the quiz.
 * - answers: The answers of the quiz.
 * - results: The results of the quiz.
 *
 * The get method takes an array of shortcode attributes and returns an instance of the Data class.
 * If the shortcode attribute 'quiz' is set, it is used to retrieve the post with the corresponding slug.
 * If the shortcode attribute 'quiz' is not set, the global $post is used to retrieve the post.
 * If the post is not found or the post type is not equal to Config::POST_TYPE, false is returned.
 */
final class Data
{
    public function __construct(
        /**
         * The ID of the post.
         * @var int
         */
        public readonly int $id,

        /**
         * The title of the post.
         * @var string
         */
        public readonly string $title,

        /**
         * The slug of the post.
         * @var string
         */
        public readonly string $slug,

        /**
         * The date of the post.
         * @var string
         */
        public readonly string $date,

        /**
         * The modified date of the post.
         * @var string
         */
        public readonly string $modified,

        /**
         * The description of the quiz.
         * @var string
         */
        public readonly string $description,

        /**
         * The instructions of the quiz.
         * @var string
         */
        public readonly string $instructions,

        /**
         * The contact section fields in the plugin settings.
         * @var array
         */
        public readonly array $contactSection,

        /**
         * The sections of the quiz.
         * @var array
         */
        public readonly array $questionSections,

        /**
         * The answers of the quiz.
         * @var array
         */
        public readonly array $answers,

        /**
         * The results of the quiz.
         * @var array
         */
        public readonly array $results
    ) {}

    /**
     * Get an instance of the Data class.
     *
     * @param array $atts The shortcode attributes.
     * @return Data|false
     */
    public static function get(array $atts)
    {
        // Look for slug in shortcode attribute or query string
        $slug = $atts['quiz'] ?? ($_GET['quiz'] ?? null);

        if (!$slug) {
            $slug = $_GET['quiz'] ?? null;
        }

        global $post;

        if ($slug) {
            $args = [
                'post_type' => Config::POST_TYPE,
                'name' => $slug,
                'post_status' => 'publish',
                'numberposts' => 1,
            ];
            $postData = get_posts($args);
            if (!empty($postData)) {
                $postData = $postData[0];
            }
        } else {
            $postData = get_post($post->ID);
        }

        if (empty($postData) || $postData->post_type !== Config::POST_TYPE) {
            return false;
        }

        // Use the correct meta key (matches RegisterHandler)
        $metaKey = '_' . Config::POST_TYPE . '_sections';

        // IMPORTANT: use the ID of the post we just found (NOT the global $post unless it is the same)
        $meta = get_post_meta((int)$postData->ID, $metaKey, true);

        // If meta somehow stored as a serialized string, attempt to unserialize
        if (is_string($meta)) {
            $maybe = maybe_unserialize($meta);
            if (is_array($maybe)) {
                $meta = $maybe;
            }
        }

        // Ensure we return arrays (not null/strings)
        if (!is_array($meta)) {
            $meta = [];
        }
        
        // Load contact settings data
        $contactSectionSettingsData = get_option(Config::PLUGIN_ABBREV . '_' . Config::CONTACT_FIELDS_SLUG, []);

        // Build a clean response array (avoid returning raw WP_Post for JSON)
        $response = [
            'id'           => (int) $post->ID,
            'title'        => get_the_title($post),
            'slug'         => $post->post_name,
            'date'         => get_the_date('', $post),
            'modified'     => get_the_modified_date('', $post),
            'description'  => $meta['description']  ?? '',
            'instructions' => $meta['instructions'] ?? '',
            'contactSection'     => $contactSectionSettingsData ?? [],
            'sections'     => $meta['sections']     ?? [],
            'answers'      => $meta['answers']      ?? [],
            'results'      => $meta['results']      ?? [],
        ];

        // Instantiate class and return the data
        $data = new self(
            $response['id'],
            $response['title'],
            $response['slug'],
            $response['date'],
            $response['modified'],
            $response['description'],
            $response['instructions'],
            $response['contactSection'],
            $response['sections'],
            $response['answers'],
            $response['results']
        );

        return $data;
    }
}