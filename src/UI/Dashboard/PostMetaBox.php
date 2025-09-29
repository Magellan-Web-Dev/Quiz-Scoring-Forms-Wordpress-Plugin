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
 * Responsibilities:
 * - Outputs the HTML structure used inside the WordPress editor.
 * - Provides scoped CSS for styling the UI.
 * - Provides vanilla JavaScript for interactivity:
 *   - Adding new sections dynamically
 *   - Removing sections
 *   - Drag-and-drop reordering
 *
 * Everything here is scoped with `$postType` and a `-metabox` suffix so it won’t
 * interfere with other WordPress dashboard UI or plugins.
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
        // Generate a security nonce to protect form submissions.
        wp_nonce_field($this->nonceAction, $this->nonceName);

        // Retrieve existing section data stored in post meta (if any).
        $sections = get_post_meta($post->ID, $this->metaKey, true);
        if (!is_array($sections)) {
            $sections = [];
        }

        // Container that will hold all sections.
        echo '<div id="' . $this->postType . '-sections-metabox" class="' . $this->postType . '-sections-metabox">';

        // Loop through existing sections and output fields for each one.
        foreach ($sections as $index => $section) {
            $title     = esc_attr($section['title'] ?? '');
            $questions = $section['questions'] ?? [];

            echo '<div class="' . $this->postType . '-section-metabox ' . $this->postType . '-draggable-metabox" draggable="true">';
            
            echo '<label>Section Title:</label><br>';
            echo '<input type="text" name="' . $this->postType . '_sections[' . $index . '][title]" value="' . $title . '" style="width:100%; margin-bottom:8px;">';

            echo '<label>Questions (one per line):</label><br>';
            
            // If questions are objects/arrays, extract the "text"
            if (!empty($questions) && is_array($questions) && isset($questions[0]['text'])) {
                $questions = array_map(fn($q) => $q['text'] ?? '', $questions);
            }

            echo '<textarea name="' . $this->postType . '_sections[' . $index . '][questions]" rows="4" style="width:100%;">' 
                . esc_textarea(implode("\n", $questions)) 
                . '</textarea>';

            echo '<button type="button" class="button ' . $this->postType . '-remove-section-metabox" style="margin-top:8px;">Remove Section</button>';

            echo '<hr>';
            echo '</div>';
        }

        echo '</div>'; // End sections container

        // Button to dynamically add new sections
        echo '<button type="button" id="add-' . $this->postType . '-section-metabox" class="button">Add Section</button>';

        // Render inline CSS and JS for the UI
        $this->renderStyles();
        $this->renderScripts(count($sections));
    }

    /**
     * Render inline CSS styles for the meta box UI.
     *
     * @return void
     */
    private function renderStyles(): void
    {
        ?>
        <style>
            .<?php echo $this->postType; ?>-sections-metabox {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .<?php echo $this->postType; ?>-section-metabox {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 12px;
                border-radius: 4px;
                cursor: grab;
            }
            .<?php echo $this->postType; ?>-section-metabox.dragging {
                opacity: 0.5;
                border: 2px dashed #0073aa;
            }
            .<?php echo $this->postType; ?>-remove-section-metabox {
                background: #d63638 !important;
                border-color: #d63638 !important;
                color: #fff !important;
                cursor: pointer;
            }
            .<?php echo $this->postType; ?>-remove-section-metabox:hover {
                background: #a4282a !important;
                border-color: #a4282a !important;
            }
        </style>
        <?php
    }

    /**
     * Render inline vanilla JavaScript for UI interactivity.
     *
     * @param int $sectionCount The number of pre-existing sections (used for indexing).
     * @return void
     */
    private function renderScripts(int $sectionCount): void
    {
        ?>
        <script>
            (function(){
                const container = document.getElementById('<?php echo $this->postType; ?>-sections-metabox');
                const addBtn = document.getElementById('add-<?php echo $this->postType; ?>-section-metabox');
                let sectionIndex = <?php echo $sectionCount; ?>;

                // Add new blank section dynamically
                addBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.className = '<?php echo $this->postType; ?>-section-metabox <?php echo $this->postType; ?>-draggable-metabox';
                    div.setAttribute('draggable', 'true');
                    div.innerHTML = `
                        <label>Section Title:</label><br>
                        <input type="text" name="<?php echo $this->postType; ?>_sections[${sectionIndex}][title]" style="width:100%; margin-bottom:8px;">
                        <label>Questions (one per line):</label><br>
                        <textarea name="<?php echo $this->postType; ?>_sections[${sectionIndex}][questions]" rows="4" style="width:100%;"></textarea>
                        <button type="button" class="button <?php echo $this->postType; ?>-remove-section-metabox" style="margin-top:8px;">Remove Section</button>
                        <hr>`;
                    
                    container.appendChild(div);
                    sectionIndex++;
                    attachDragEvents(div);
                    attachRemoveEvent(div.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'));
                });

                // Drag-and-drop setup
                let dragged;
                function attachDragEvents(el) {
                    el.addEventListener('dragstart', () => {
                        dragged = el;
                        el.classList.add('dragging');
                    });
                    el.addEventListener('dragend', () => {
                        dragged = null;
                        el.classList.remove('dragging');
                    });
                    el.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        const bounding = el.getBoundingClientRect();
                        const offset = bounding.y + bounding.height / 2;

                        if (e.clientY - offset > 0) {
                            el.style['border-bottom'] = "2px solid #0073aa";
                            el.style['border-top'] = "";
                        } else {
                            el.style['border-top'] = "2px solid #0073aa";
                            el.style['border-bottom'] = "";
                        }
                    });
                    el.addEventListener('dragleave', () => {
                        el.style['border-bottom'] = "";
                        el.style['border-top'] = "";
                    });
                    el.addEventListener('drop', (e) => {
                        e.preventDefault();
                        el.style['border-bottom'] = "";
                        el.style['border-top'] = "";
                        if (e.clientY < el.getBoundingClientRect().y + el.offsetHeight / 2) {
                            container.insertBefore(dragged, el);
                        } else {
                            container.insertBefore(dragged, el.nextSibling);
                        }
                    });
                }

                // Remove button handler
                function attachRemoveEvent(btn) {
                    btn.addEventListener('click', () => {
                        const section = btn.closest('.<?php echo $this->postType; ?>-section-metabox');
                        if (section) section.remove();
                    });
                }

                // Initialize events for existing sections
                container.querySelectorAll('.<?php echo $this->postType; ?>-section-metabox').forEach(section => {
                    attachDragEvents(section);
                    attachRemoveEvent(section.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'));
                });
            })();
        </script>
        <?php
    }
}