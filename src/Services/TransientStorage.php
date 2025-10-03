<?php

declare(strict_types=1);

namespace QuizScoringForms\Services;

use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class TransientStorage
 *
 * Handles storage and retrieval of transient data using the Wordpress transient API.
 *
 * @package QuizScoringForms\Services
 */
class TransientStorage {

    /**
     * The key used to store and retrieve transient data.
     *
     * @var string
     */
    public readonly string $key;

    /**
     * The time to expiration of the transient data in seconds.
     *
     * @var int
     */
    public readonly int $timeToExpiration;

    /**
     * Constructor
     *
     * @param string $key The key used to store and retrieve transient data.
     * @param int $timeToExpiration The time to expiration of the transient data in seconds.
     */
    public function __construct(string $key, int $timeToExpiration) 
    {
        $this->key = $key;
        $this->timeToExpiration = $timeToExpiration;
    }

    /**
     * Retrieves the transient data associated with the provided key.
     *
     * If $concatId is provided, it will be concatenated to the key.
     *
     * @param string $concatId The id to concatenate to the key.
     * @return mixed The transient data associated with the provided key.
     */
    public function get($concatId = ''): mixed 
    {
        return get_transient(Config::SLUG_UNDERSCORE . '_' . $this->key . $this->parseConcatId($concatId));
    }

    /**
     * Sets the transient data associated with the provided key.
     *
     * If $concatId is provided, it will be concatenated to the key.
     *
     * @param mixed $value The value to store as transient data.
     * @param string $concatId The id to concatenate to the key.
     * @return void
     */
    public function set($value, $concatId = ''): void 
    {
        set_transient(Config::SLUG_UNDERSCORE . '_' . $this->key . $this->parseConcatId($concatId), $value, $this->timeToExpiration);
    }

    /**
     * Deletes the transient data associated with the provided key.
     *
     * If $concatId is provided, it will be concatenated to the key.
     *
     * @param string $concatId The id to concatenate to the key.
     * @return void
     */
    public function delete($concatId = ''): void 
    {
        delete_transient(Config::SLUG_UNDERSCORE . '_' . $this->key . $this->parseConcatId($concatId));
    }

    /**
     * Concatenates the provided id to the key.
     * 
     * @param string $concatId The id to concatenate to the key.
     * @return string The concatenated id.
     */
    private function parseConcatId($concatId): string 
    {
        if ($concatId !== '') {
            return '_' . $concatId;
        }
        return '';
    }
}