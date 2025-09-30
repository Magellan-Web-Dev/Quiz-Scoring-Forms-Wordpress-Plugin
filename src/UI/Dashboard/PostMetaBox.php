<?php

declare(strict_types=1);

namespace QuizScoringForms\UI\Dashboard;

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
 * - Renders inputs for sections, questions, and global answers
 * - Provides JS interactivity:
 *   - Add/remove sections
 *   - Add/remove answers
 *   - Drag-and-drop reordering for sections
 * - Provides scoped CSS so styles only affect this metabox
 */
final class PostMetaBox
{
    public function __construct(
        private readonly string $postType,
        private readonly string $metaKey,
        private readonly string $nonceAction,
        private readonly string $nonceName
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

        // Load saved data or default
        $data = get_post_meta($post->ID, $this->metaKey, true);
        if (!is_array($data)) {
            $data = ['sections' => [], 'answers' => []];
        }

        $sections = $data['sections'] ?? [];
        $answers  = $data['answers'] ?? [];

        // --- Sections UI ---
        echo '<div id="' . $this->postType . '-sections-metabox" class="' . $this->postType . '-sections-metabox">';

        foreach ($sections as $index => $section) {
            $title     = esc_attr($section['title'] ?? '');
            $questions = $section['questions'] ?? [];

            // Ensure questions render as text lines
            if (!empty($questions) && is_array($questions)) {
                $questions = array_map(fn($q) => is_array($q) ? ($q['text'] ?? '') : $q, $questions);
            }

            echo '<div class="' . $this->postType . '-section-metabox ' . $this->postType . '-draggable-metabox">';
            
            echo '<label>Section Title:</label><br>';
            echo '<input type="text" name="' . $this->postType . '_data[sections][' . $index . '][title]" value="' . $title . '" style="width:100%; margin-bottom:8px;">';

            echo '<label>Questions (one per line):</label><br>';
            echo '<textarea name="' . $this->postType . '_data[sections][' . $index . '][questions]" rows="4" style="width:100%;">' 
                . esc_textarea(implode("\n", $questions)) 
                . '</textarea>';

            // Remove + Drag handle container
            echo '<div class="' . $this->postType . '-section-actions">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-section-metabox">Remove Section</button>';
            echo '<span class="' . $this->postType . '-drag-handle" role="button" tabindex="0" title="Click and hold to drag">☰</span>';
            echo '</div>';

            echo '<hr>';
            echo '</div>';
        }

        echo '</div>'; // end sections container

        echo '<button type="button" id="add-' . $this->postType . '-section-metabox" class="button" style="margin-top:8px;">Add Section</button>';

        // --- Global Answer Options ---
        echo '<h4 style="margin-top:16px;">Answer Options (applies to all questions)</h4>';
        echo '<div id="' . $this->postType . '-answers-metabox" class="' . $this->postType . '-answers-metabox">';
        foreach ($answers as $i => $answer) {
            $text  = esc_attr($answer['text'] ?? '');
            $value = esc_attr($answer['value'] ?? '');
            echo '<div class="' . $this->postType . '-answer-metabox">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][text]" value="' . $text . '" placeholder="Answer text" style="width:45%; margin-right:8px;">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][value]" value="' . $value . '" placeholder="Value" style="width:45%;">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-answer-metabox" style="margin-top:12px;">Remove Answer</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-' . $this->postType . '-answer-metabox" class="button" style="margin-top:8px;">Add Answer</button>';

        $this->renderStyles();
        $this->renderScripts(count($sections), count($answers));
    }

    /**
     * Render scoped styles for the metabox UI
     */
    private function renderStyles(): void
    {
        ?>
        <style>
            .<?php echo $this->postType; ?>-sections-metabox,
            .<?php echo $this->postType; ?>-answers-metabox {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-top: 12px;
            }

            .<?php echo $this->postType; ?>-section-metabox,
            .<?php echo $this->postType; ?>-answer-metabox {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 12px;
                border-radius: 4px;
            }

            .<?php echo $this->postType; ?>-section-metabox.dragging {
                opacity: 0.5;
                border: 2px dashed #0073aa;
            }

            /* Flex container for remove + drag */
            .<?php echo $this->postType; ?>-section-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 8px;
                flex-wrap: wrap;
                gap: 8px;
            }

            /* Red remove buttons */
            .<?php echo $this->postType; ?>-remove-section-metabox,
            .<?php echo $this->postType; ?>-remove-answer-metabox {
                background: #d63638 !important;
                border-color: #d63638 !important;
                color: #fff !important;
                cursor: pointer;
            }
            .<?php echo $this->postType; ?>-remove-section-metabox:hover,
            .<?php echo $this->postType; ?>-remove-answer-metabox:hover {
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
     * Render scoped scripts for add/remove/drag functionality
     *
     * @param int $sectionCount Number of existing sections
     * @param int $answerCount Number of existing answers
     */
    private function renderScripts(int $sectionCount, int $answerCount): void
    {
        ?>
        <script>
        (function(){
            const container = document.getElementById('<?php echo $this->postType; ?>-sections-metabox');
            const addBtn = document.getElementById('add-<?php echo $this->postType; ?>-section-metabox');
            let sectionIndex = <?php echo $sectionCount; ?>;
            let dragged = null;

            // --- Section helpers ---
            function attachRemoveSectionEvent(btn) {
                if (!btn) return;
                btn.addEventListener('click', () => {
                    const parent = btn.closest('.<?php echo $this->postType; ?>-section-metabox');
                    if (parent) parent.remove();
                });
            }

            // --- Drag helpers ---
            function attachDragHandlers(section) {
                section.draggable = false;

                section.addEventListener('dragstart', (e) => {
                    dragged = section;
                    section.classList.add('dragging');
                    try { e.dataTransfer.setData('text/plain', ''); } catch (err) {}
                });

                section.addEventListener('dragend', () => {
                    section.classList.remove('dragging');
                    dragged = null;
                    container.querySelectorAll('.<?php echo $this->postType; ?>-section-metabox').forEach(s => {
                        s.style.borderTop = s.style.borderBottom = '';
                    });
                });

                section.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    if (section === dragged) return;
                    const bounding = section.getBoundingClientRect();
                    const offset = bounding.y + bounding.height / 2;
                    if (e.clientY - offset > 0) {
                        section.style.borderBottom = "2px solid #0073aa";
                        section.style.borderTop = "";
                    } else {
                        section.style.borderTop = "2px solid #0073aa";
                        section.style.borderBottom = "";
                    }
                });

                section.addEventListener('dragleave', () => {
                    section.style.borderTop = section.style.borderBottom = "";
                });

                section.addEventListener('drop', (e) => {
                    e.preventDefault();
                    section.style.borderTop = section.style.borderBottom = "";
                    if (!dragged) return;
                    if (e.clientY < section.getBoundingClientRect().y + section.offsetHeight / 2) {
                        container.insertBefore(dragged, section);
                    } else {
                        container.insertBefore(dragged, section.nextSibling);
                    }
                });

                // Prevent drag activation on inputs/buttons
                section.querySelectorAll('input, textarea, button').forEach(el => {
                    el.addEventListener('mousedown', ev => ev.stopPropagation());
                    el.addEventListener('dragstart', ev => ev.preventDefault());
                });
            }

            // --- Handle helpers ---
            function attachHandle(handle) {
                if (!handle) return;
                const section = handle.closest('.<?php echo $this->postType; ?>-section-metabox');
                if (!section) return;

                handle.addEventListener('mousedown', (e) => {
                    if (e.button !== 0) return;
                    section.draggable = true;
                });

                document.addEventListener('mouseup', () => {
                    container.querySelectorAll('.<?php echo $this->postType; ?>-section-metabox').forEach(s => s.draggable = false);
                });
            }

            // Init existing sections
            container.querySelectorAll('.<?php echo $this->postType; ?>-section-metabox').forEach(section => {
                attachDragHandlers(section);
                attachHandle(section.querySelector('.<?php echo $this->postType; ?>-drag-handle'));
                attachRemoveSectionEvent(section.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'));
            });

            // Add new section
            addBtn.addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-section-metabox <?php echo $this->postType; ?>-draggable-metabox';
                div.innerHTML = `
                    <label>Section Title:</label><br>
                    <input type="text" name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][title]" style="width:100%; margin-bottom:8px;">
                    <label>Questions (one per line):</label><br>
                    <textarea name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][questions]" rows="4" style="width:100%;"></textarea>
                    <div class="<?php echo $this->postType; ?>-section-actions">
                        <button type="button" class="button <?php echo $this->postType; ?>-remove-section-metabox">Remove Section</button>
                        <span class="<?php echo $this->postType; ?>-drag-handle" role="button" tabindex="0" title="Click and hold to drag">☰</span>
                    </div>
                    <hr>`;
                container.appendChild(div);

                attachDragHandlers(div);
                attachHandle(div.querySelector('.<?php echo $this->postType; ?>-drag-handle'));
                attachRemoveSectionEvent(div.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'));

                sectionIndex++;
            });

            // --- Answers ---
            const answersContainer = document.getElementById('<?php echo $this->postType; ?>-answers-metabox');
            const addAnswerBtn = document.getElementById('add-<?php echo $this->postType; ?>-answer-metabox');
            let answerIndex = <?php echo $answerCount; ?>;

            function attachRemoveAnswerEvent(btn) {
                if (!btn) return;
                btn.addEventListener('click', () => {
                    const parent = btn.closest('.<?php echo $this->postType; ?>-answer-metabox');
                    if (parent) parent.remove();
                });
            }

            // Init existing answers
            answersContainer.querySelectorAll('.<?php echo $this->postType; ?>-remove-answer-metabox').forEach(btn => {
                attachRemoveAnswerEvent(btn);
            });

            // Add new answer
            addAnswerBtn.addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-answer-metabox';
                div.innerHTML = `
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][text]" placeholder="Answer text" style="width:45%; margin-right:8px;">
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][value]" placeholder="Value" style="width:45%;">
                    <button type="button" class="button <?php echo $this->postType; ?>-remove-answer-metabox" style="margin-top:12px;">Remove Answer</button>`;
                answersContainer.appendChild(div);

                attachRemoveAnswerEvent(div.querySelector('.<?php echo $this->postType; ?>-remove-answer-metabox'));
                answerIndex++;
            });

        })();
        </script>
        <?php
    }
}