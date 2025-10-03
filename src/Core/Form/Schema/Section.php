<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form\Schema;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Section
 *
 * Represents a form section with associated title, slug, and order.
 *
 * This class is a simple data container for form sections and their associated metadata.
 */
final class Section
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly int $order,
        public readonly array $fields,
        public readonly bool $questionSection
    ) {}
}