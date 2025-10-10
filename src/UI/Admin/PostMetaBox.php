<?php

declare(strict_types=1);

namespace QuizScoringForms\UI\Admin;

/** 
 * Prevent direct access from sources other than the WordPress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class PostMetaBox
 *
 * Handles rendering of the custom meta box UI for the quiz post type.
 *
 * Features:
 * - Renders inputs for description, instructions, sections, questions, answers, and results
 * - Provides JS interactivity:
 *   - Add/remove sections, answers, results
 *   - Drag-and-drop reordering for sections, answers, and results
 * - Provides scoped CSS so styles only affect this metabox
 */
final class PostMetaBox
{
    public function __construct(
        public readonly string $postType,
        public readonly string $metaKey,
        public readonly string $nonceAction,
        public readonly string $nonceName
    ) {}

    /**
     * Render the meta box UI inside the WordPress editor.
     *
     * @param \WP_Post $post The current WordPress post object being edited.
     * @return void
     */
    public function render(\WP_Post $post): void
    {
        wp_nonce_field($this->nonceAction, $this->nonceName);

        // First, check if we have validation data from the last save attempt
        $validationData = get_transient("quiz_validation_data_{$post->ID}");

        if ($validationData !== false) {
            // Use the submitted data (invalid but user-entered)
            $data = $validationData;
            // Clear it so it only shows once
            delete_transient("quiz_validation_data_{$post->ID}");
        } else {
            // Fallback to saved post meta
            $data = get_post_meta($post->ID, $this->metaKey, true) ?: [];
        }
        
        if (!is_array($data)) {
            $data = [
                'description'  => '',
                'instructions' => '',
                'sections'     => [],
                'answers'      => [],
                'results'      => [],
            ];
        }

        $description  = $data['description'] ?? '';
        $instructions = $data['instructions'] ?? '';
        $sections     = $data['sections'] ?? [];
        $answers      = $data['answers'] ?? [];
        $results      = $data['results'] ?? [];

        echo '<div class="' . $this->postType . '-metabox">';

        // --- Description ---
        echo '<h4>Description</h4>';
        echo '<textarea name="' . $this->postType . '_data[description]" rows="3" style="width:100%;">' . esc_textarea($description) . '</textarea>';

        // --- Instructions ---
        echo '<h4 style="margin-top:16px;">Instructions</h4>';
        echo '<textarea name="' . $this->postType . '_data[instructions]" rows="3" style="width:100%;">' . esc_textarea($instructions) . '</textarea>';

        // --- Sections ---
        echo '<h4 style="margin-top:16px;">Sections</h4>';
        echo '<div id="' . $this->postType . '-sections-metabox">';
        foreach ($sections as $index => $section) {
            $title     = esc_attr($section['title'] ?? '');
            $questions = $section['questions'] ?? [];
            if (!empty($questions) && is_array($questions)) {
                $questions = array_map(fn($q) => is_array($q) ? ($q['text'] ?? '') : $q, $questions);
            }

            echo '<div class="' . $this->postType . '-section-metabox">';
            echo '<label>Section Title:</label><br>';
            echo '<input type="text" name="' . $this->postType . '_data[sections][' . $index . '][title]" value="' . $title . '" style="width:100%; margin-bottom:8px;">';
            echo '<label>Questions (one per line):</label><br>';
            echo '<textarea name="' . $this->postType . '_data[sections][' . $index . '][questions]" rows="4" style="width:100%;">' . esc_textarea(implode("\n", $questions)) . '</textarea>';

            // Actions row (remove + drag)
            echo '<div class="' . $this->postType . '-item-actions">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-section-metabox" style="margin-top:12px;">Remove Section</button>';
            echo '<span class="' . $this->postType . '-drag-handle" role="button" tabindex="0">☰</span>';
            echo '</div>';

            echo '<hr></div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-' . $this->postType . '-section-metabox" class="button" style="margin-top:8px;">Add Section</button>';

        // --- Answers ---
        echo '<h4 style="margin-top:16px;">Answers (global)</h4>';
        echo '<div id="' . $this->postType . '-answers-metabox">';
        foreach ($answers as $i => $answer) {
            $text  = esc_attr($answer['text'] ?? '');
            $value = esc_attr($answer['value'] ?? '');
            echo '<div class="' . $this->postType . '-answer-metabox">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][text]" value="' . $text . '" placeholder="Answer text" style="width:45%; margin-right:8px;">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][value]" value="' . $value . '" placeholder="Value" style="width:45%;">';

            // Actions row (remove + drag)
            echo '<div class="' . $this->postType . '-item-actions">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-answer-metabox" style="margin-top:12px;">Remove Answer</button>';
            echo '<span class="' . $this->postType . '-drag-handle" role="button" tabindex="0">☰</span>';
            echo '</div>';

            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-' . $this->postType . '-answer-metabox" class="button" style="margin-top:8px;">Add Answer</button>';

        // --- Results ---
        echo '<h4 style="margin-top:16px;">Results</h4>';
        echo '<div id="' . $this->postType . '-results-metabox">';
        foreach ($results as $i => $result) {
            $title = esc_attr($result['title'] ?? '');
            $desc  = esc_textarea($result['description'] ?? '');
            $min   = esc_attr($result['min_percentage'] ?? '');
            $max   = esc_attr($result['max_percentage'] ?? '');

            echo '<div class="' . $this->postType . '-result-metabox">';
            echo '<input type="text" name="' . $this->postType . '_data[results][' . $i . '][title]" value="' . $title . '" placeholder="Result title" style="width:45%; margin-right:8px;">';
            echo '<textarea name="' . $this->postType . '_data[results][' . $i . '][description]" rows="2" placeholder="Result description" style="width:100%; margin:8px 0;">' . $desc . '</textarea>';
            echo '<input type="number" name="' . $this->postType . '_data[results][' . $i . '][min_percentage]" value="' . $min . '" placeholder="Min %" style="width:45%; margin-right:8px;">';
            echo '<input type="number" name="' . $this->postType . '_data[results][' . $i . '][max_percentage]" value="' . $max . '" placeholder="Max %" style="width:45%;">';

            // Actions row (remove + drag)
            echo '<div class="' . $this->postType . '-item-actions">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-result-metabox" style="margin-top:12px;">Remove Result</button>';
            echo '<span class="' . $this->postType . '-drag-handle" role="button" tabindex="0">☰</span>';
            echo '</div>';

            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-' . $this->postType . '-result-metabox" class="button" style="margin-top:8px;">Add Result</button>';

        echo '</div>';

        $this->renderStyles();
        $this->renderScripts(count($sections), count($answers), count($results));
    }

    /**
     * Render scoped styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
            /* Box styling for each item */
            .<?php echo $this->postType; ?>-section-metabox,
            .<?php echo $this->postType; ?>-answer-metabox,
            .<?php echo $this->postType; ?>-result-metabox {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 8px;
            }

            /* Highlight when dragging */
            .<?php echo $this->postType; ?>-section-metabox.dragging,
            .<?php echo $this->postType; ?>-answer-metabox.dragging,
            .<?php echo $this->postType; ?>-result-metabox.dragging {
                opacity: 0.5;
                border: 2px dashed #0073aa;
            }

            /* Action bar for remove + drag handle */
            .<?php echo $this->postType; ?>-item-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 8px;
                flex-wrap: wrap;
                gap: 8px;
            }

            /* Red remove buttons */
            .<?php echo $this->postType; ?>-remove-section-metabox,
            .<?php echo $this->postType; ?>-remove-answer-metabox,
            .<?php echo $this->postType; ?>-remove-result-metabox {
                background: #d63638 !important;
                border-color: #d63638 !important;
                color: #fff !important;
                cursor: pointer;
            }
            .<?php echo $this->postType; ?>-remove-section-metabox:hover,
            .<?php echo $this->postType; ?>-remove-answer-metabox:hover,
            .<?php echo $this->postType; ?>-remove-result-metabox:hover {
                background: #a4282a !important;
                border-color: #a4282a !important;
            }

            /* Drag handle */
            .<?php echo $this->postType; ?>-drag-handle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                border: 1px solid #cfcfcf;
                background: #fff;
                cursor: grab;
                font-weight: 600;
                user-select: none;
                padding: 4px 8px;
                min-width: 36px;
                text-align: center;
                box-shadow: 0 1px 0 rgba(0,0,0,0.04);
            }
            .<?php echo $this->postType; ?>-drag-handle:active {
                cursor: grabbing;
            }
        </style>
        <?php
    }

    /**
     * Render scoped scripts (handles add/remove and drag/drop)
     */
    private function renderScripts(int $sectionCount, int $answerCount, int $resultCount): void
    {
        ?>
        <script>
        (function(){
            let sectionIndex = <?php echo $sectionCount; ?>;
            let answerIndex  = <?php echo $answerCount; ?>;
            let resultIndex  = <?php echo $resultCount; ?>;
            let dragged = null;
            let draggedContainer = null;

            /**
             * Attach remove button logic
             */
            function attachRemove(btn, selector) {
                if (!btn) return;
                btn.addEventListener('click', () => {
                    const parent = btn.closest(selector);
                    if (parent) parent.remove();
                });
            }

            /**
             * Attach drag-and-drop logic to a given element
             */
            function attachDragHandlers(item, container, selector) {
                if (!item) return;
                item.draggable = false;

                item.addEventListener('dragstart', (e) => {
                    dragged = item;
                    draggedContainer = container; // 🔑 remember where this came from
                    item.classList.add('dragging');
                    try { e.dataTransfer.setData('text/plain', ''); } catch (err) {}
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    dragged = null;
                    draggedContainer = null;
                    container.querySelectorAll(selector).forEach(el => {
                        el.style.borderTop = el.style.borderBottom = '';
                    });
                });

                item.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    if (item === dragged) return;
                    if (draggedContainer !== container) return; // 🔑 block cross-container drops
                    const bounding = item.getBoundingClientRect();
                    const offset = bounding.y + bounding.height / 2;
                    if (e.clientY - offset > 0) {
                        item.style.borderBottom = "2px solid #0073aa";
                        item.style.borderTop = "";
                    } else {
                        item.style.borderTop = "2px solid #0073aa";
                        item.style.borderBottom = "";
                    }
                });

                item.addEventListener('dragleave', () => {
                    item.style.borderTop = item.style.borderBottom = "";
                });

                item.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (!dragged) return;
                    if (draggedContainer !== container) return; // 🔑 only same container
                    item.style.borderTop = item.style.borderBottom = "";
                    if (e.clientY < item.getBoundingClientRect().y + item.offsetHeight / 2) {
                        container.insertBefore(dragged, item);
                    } else {
                        container.insertBefore(dragged, item.nextSibling);
                    }
                });

                // Prevent drag activation on inputs/buttons
                item.querySelectorAll('input, textarea, button').forEach(el => {
                    el.addEventListener('mousedown', ev => ev.stopPropagation());
                    el.addEventListener('dragstart', ev => ev.preventDefault());
                });

                // Drag handle activation
                const handle = item.querySelector('.<?php echo $this->postType; ?>-drag-handle');
                if (handle) {
                    handle.addEventListener('mousedown', (e) => {
                        if (e.button !== 0) return;
                        item.draggable = true;
                    });
                    document.addEventListener('mouseup', () => {
                        item.draggable = false;
                    });
                }
            }

            /**
             * Initialize drag + remove on all existing items
             */
            function initItems(containerId, selector, removeClass) {
                const container = document.getElementById(containerId);
                container.querySelectorAll(selector).forEach(item => {
                    attachRemove(item.querySelector(removeClass), selector);
                    attachDragHandlers(item, container, selector);
                });
                return container;
            }

            const sectionContainer = initItems('<?php echo $this->postType; ?>-sections-metabox', '.<?php echo $this->postType; ?>-section-metabox', '.<?php echo $this->postType; ?>-remove-section-metabox');
            const answerContainer  = initItems('<?php echo $this->postType; ?>-answers-metabox', '.<?php echo $this->postType; ?>-answer-metabox', '.<?php echo $this->postType; ?>-remove-answer-metabox');
            const resultContainer  = initItems('<?php echo $this->postType; ?>-results-metabox', '.<?php echo $this->postType; ?>-result-metabox', '.<?php echo $this->postType; ?>-remove-result-metabox');

            // Add Section
            document.getElementById('add-<?php echo $this->postType; ?>-section-metabox').addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-section-metabox';
                div.innerHTML = `
                    <label>Section Title:</label><br>
                    <input type="text" name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][title]" style="width:100%; margin-bottom:8px;">
                    <label>Questions (one per line):</label><br>
                    <textarea name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][questions]" rows="4" style="width:100%;"></textarea>
                    <div class="<?php echo $this->postType; ?>-item-actions">
                        <button type="button" class="button <?php echo $this->postType; ?>-remove-section-metabox" style="margin-top:12px;">Remove Section</button>
                        <span class="<?php echo $this->postType; ?>-drag-handle">☰</span>
                    </div>
                    <hr>`;
                sectionContainer.appendChild(div);
                attachRemove(div.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'), '.<?php echo $this->postType; ?>-section-metabox');
                attachDragHandlers(div, sectionContainer, '.<?php echo $this->postType; ?>-section-metabox');
                sectionIndex++;
            });

            // Add Answer
            document.getElementById('add-<?php echo $this->postType; ?>-answer-metabox').addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-answer-metabox';
                div.innerHTML = `
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][text]" placeholder="Answer text" style="width:45%; margin-right:8px;">
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][value]" placeholder="Value" style="width:45%;">
                    <div class="<?php echo $this->postType; ?>-item-actions">
                        <button type="button" class="button <?php echo $this->postType; ?>-remove-answer-metabox" style="margin-top:12px;">Remove Answer</button>
                        <span class="<?php echo $this->postType; ?>-drag-handle">☰</span>
                    </div>`;
                answerContainer.appendChild(div);
                attachRemove(div.querySelector('.<?php echo $this->postType; ?>-remove-answer-metabox'), '.<?php echo $this->postType; ?>-answer-metabox');
                attachDragHandlers(div, answerContainer, '.<?php echo $this->postType; ?>-answer-metabox');
                answerIndex++;
            });

            // Add Result
            document.getElementById('add-<?php echo $this->postType; ?>-result-metabox').addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-result-metabox';
                div.innerHTML = `
                    <input type="text" name="<?php echo $this->postType; ?>_data[results][${resultIndex}][title]" placeholder="Result title" style="width:45%; margin-right:8px;">
                    <textarea name="<?php echo $this->postType; ?>_data[results][${resultIndex}][description]" rows="2" placeholder="Result description" style="width:100%; margin:8px 0;"></textarea>
                    <input type="number" name="<?php echo $this->postType; ?>_data[results][${resultIndex}][min_percentage]" placeholder="Min %" style="width:45%; margin-right:8px;">
                    <input type="number" name="<?php echo $this->postType; ?>_data[results][${resultIndex}][max_percentage]" placeholder="Max %" style="width:45%;">
                    <div class="<?php echo $this->postType; ?>-item-actions">
                        <button type="button" class="button <?php echo $this->postType; ?>-remove-result-metabox" style="margin-top:12px;">Remove Result</button>
                        <span class="<?php echo $this->postType; ?>-drag-handle">☰</span>
                    </div>`;
                resultContainer.appendChild(div);
                attachRemove(div.querySelector('.<?php echo $this->postType; ?>-remove-result-metabox'), '.<?php echo $this->postType; ?>-result-metabox');
                attachDragHandlers(div, resultContainer, '.<?php echo $this->postType; ?>-result-metabox');
                resultIndex++;
            });

        })();
        </script>
        <?php
    }
}