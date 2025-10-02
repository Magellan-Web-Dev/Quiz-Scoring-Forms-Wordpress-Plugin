<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Shortcode;

use QuizScoringForms\Config;
use QuizScoringForms\Services\ErrorGenerator;
use QuizScoringForms\Core\Form\DataHandler as FormDataHandler;
use QuizScoringForms\UI\Shortcode\Render;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Initializer {

    public FormDataHandler $formData;

    public function __construct() {
        add_shortcode(Config::SLUG, [$this, 'shortcodeCallbackInit']);
    }

    public function shortcodeCallbackInit(array $atts) {
        $postData = $this->getQuizData($atts);
        if (!$postData) {
            return Render::renderErrMsg('No quiz post data could be found.');
        }
        $this->formData = new FormDataHandler($postData);
    }

    private function getQuizData(array $atts) {
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

        // Build a clean response array (avoid returning raw WP_Post for JSON)
        $response = [
            'id'           => (int) $post->ID,
            'title'        => get_the_title($post),
            'slug'         => $post->post_name,
            'date'         => get_the_date('', $post),
            'modified'     => get_the_modified_date('', $post),
            'description'  => $meta['description']  ?? '',
            'instructions' => $meta['instructions'] ?? '',
            'sections'     => $meta['sections']     ?? [],
            'answers'      => $meta['answers']      ?? [],
            'results'      => $meta['results']      ?? [],
        ];

        return $response;
    }
}