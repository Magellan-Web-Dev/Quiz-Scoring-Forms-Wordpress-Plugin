<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form\API;

use QuizScoringForms\Config;
use QuizScoringForms\API\Form\Controller;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Router
 * 
 * Handles all REST API routes related to form submission
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
    public readonly Controller $controller;
    
    /**
     * The namespace for the REST API routes.
     * 
     * @var string
     */
    public readonly string $namespace;
    
    /**
     * The base route for the REST API routes.
     * 
     * @var string
     */
    public readonly string $restBase;

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

        $this->formSubmission();
    }

    /**
     * Register the GET /{$this->namespace}/v1/{$this->restBase} endpoint.
     * 
     * @return void
     */
    public function formSubmission() {
        register_rest_route($this->namespace, '/' . $this->restBase, [
            'methods'             => 'POST',
            'callback'            => [$this->controller, 'handleFormSubmission'],
            'permission_callback' => '__return_true', // public read access
            'args'                => $this->controller->getCollectionParams(),
        ]);
    }
}