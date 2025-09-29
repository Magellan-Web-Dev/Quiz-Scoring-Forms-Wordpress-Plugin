<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\API\Post;

use QuizScoringForms\Config;
use QuizScoringForms\Core\API\Post\Controller;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Router
 * 
 * Handles all REST API routes related to quizzes
 * 
 * Responsibilities:
 * - Register custom REST API routes for quizzes
 * - Delegate request handling to the {@see Controller} class
 */
final class Router
{

    /**
     * The namespace for the REST API routes.
     * 
     * @var Controller
     */
    private Controller $controller;
    
    /**
     * The namespace for the REST API routes.
     * 
     * @var string
     */
    private string $namespace;
    
    /**
     * The base route for the REST API routes.
     * 
     * @var string
     */
    private string $restBase;

    /**
     * Constructor
     * 
     * Initializes the Router class.
     * 
     * @param string $namespace The namespace for the REST API routes.
     * @param string $restBase The base route for the REST API routes.
     */
    public function __construct($namespace, $restBase)
    {
        $this->controller = new Controller();
        $this->namespace  = $namespace . '/v1'; 
        $this->restBase   = strtolower($restBase);

        add_action('rest_api_init', function() {
            $this->getAllPosts();
            $this->getPostById();
        });
    }

    /**
     * Register the GET /{$this->namespace}/v1/{$this->restBase} endpoint.
     * 
     * @return void
     */
    public function getAllPosts() {
        register_rest_route($this->namespace, '/' . $this->restBase, [
            'methods'             => 'GET',
            'callback'            => [$this->controller, 'getQuizzes'],
            'permission_callback' => '__return_true', // public read access
            'args'                => $this->controller->getCollectionParams(),
        ]);
    }

    /**
     * Register the GET /{$this->namespace}/v1/{$this->restBase}/id endpoint.
     * 
     * @return void
     */
    public function getPostById() {
        register_rest_route($this->namespace, '/' . $this->restBase . '/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this->controller, 'getQuiz'],
            'permission_callback' => '__return_true',
        ]);
    }
}