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
        wp_nonce_field($this->nonceAction, $this->nonceName);

        $data = get_post_meta($post->ID, $this->metaKey, true);
        if (!is_array($data)) {
            $data = ['sections' => [], 'answers' => []];
        }

        $sections = $data['sections'] ?? [];
        $answers  = $data['answers'] ?? [];

        echo '<div id="' . $this->postType . '-sections-metabox" class="' . $this->postType . '-sections-metabox">';
        foreach ($sections as $index => $section) {
            $title     = esc_attr($section['title'] ?? '');
            $questions = $section['questions'] ?? [];

            echo '<div class="' . $this->postType . '-section-metabox ' . $this->postType . '-draggable-metabox" draggable="true">';
            
            echo '<label>Section Title:</label><br>';
            echo '<input type="text" name="' . $this->postType . '_data[sections][' . $index . '][title]" value="' . $title . '" style="width:100%; margin-bottom:8px;">';

            echo '<label>Questions (one per line):</label><br>';
            if (!empty($questions) && is_array($questions)) {
                $questions = array_map(fn($q) => is_array($q) ? ($q['text'] ?? '') : $q, $questions);
            }
            echo '<textarea name="' . $this->postType . '_data[sections][' . $index . '][questions]" rows="4" style="width:100%;">' 
                . esc_textarea(implode("\n", $questions)) 
                . '</textarea>';

            echo '<button type="button" class="button ' . $this->postType . '-remove-section-metabox" style="margin-top:8px;">Remove Section</button>';
            echo '<hr>';
            echo '</div>';
        }
        echo '</div>'; // end sections container

        echo '<button type="button" id="add-' . $this->postType . '-section-metabox" class="button" style="margin-top:8px;">Add Section</button>';

        // 🔹 Shared Answer Options
        echo '<h4>Answer Options (applies to all questions)</h4>';
        echo '<div id="' . $this->postType . '-answers-metabox" class="' . $this->postType . '-answers-metabox">';
        foreach ($answers as $i => $answer) {
            $text  = esc_attr($answer['text'] ?? '');
            $value = esc_attr($answer['value'] ?? '');
            echo '<div class="' . $this->postType . '-answer-metabox">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][text]" value="' . $text . '" placeholder="Answer text" style="width:45%; margin-right:8px;">';
            echo '<input type="text" name="' . $this->postType . '_data[answers][' . $i . '][value]" value="' . $value . '" placeholder="Value" style="width:45%;">';
            echo '<button type="button" class="button ' . $this->postType . '-remove-answer-metabox">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-' . $this->postType . '-answer-metabox" class="button" style="margin-top:8px;">Add Answer</button>';

        $this->renderStyles();
        $this->renderScripts(count($sections), count($answers));
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
            .<?php echo $this->postType; ?>-remove-section-metabox,
            .<?php echo $this->postType; ?>-remove-answer-metabox {
                margin-left: 8px;
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
        </style>
        <?php
    }

    /**
     * Render inline vanilla JavaScript for UI interactivity.
     *
     * @param int $sectionCount The number of pre-existing sections (used for indexing).
     * @return void
     */
    private function renderScripts(int $sectionCount, int $answerCount): void
    {
        ?>
        <script>
        (function(){
            // --- Sections
            const container = document.getElementById('<?php echo $this->postType; ?>-sections-metabox');
            const addBtn = document.getElementById('add-<?php echo $this->postType; ?>-section-metabox');
            let sectionIndex = <?php echo $sectionCount; ?>;

            addBtn.addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-section-metabox <?php echo $this->postType; ?>-draggable-metabox';
                div.setAttribute('draggable', 'true');
                div.innerHTML = `
                    <label>Section Title:</label><br>
                    <input type="text" name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][title]" style="width:100%; margin-bottom:8px;">
                    <label>Questions (one per line):</label><br>
                    <textarea name="<?php echo $this->postType; ?>_data[sections][${sectionIndex}][questions]" rows="4" style="width:100%;"></textarea>
                    <button type="button" class="button <?php echo $this->postType; ?>-remove-section-metabox" style="margin-top:8px;">Remove Section</button>
                    <hr>`;
                container.appendChild(div);
                sectionIndex++;
                attachRemoveEvent(div.querySelector('.<?php echo $this->postType; ?>-remove-section-metabox'));
            });

            function attachRemoveEvent(btn) {
                btn.addEventListener('click', () => {
                    const parent = btn.closest('div');
                    if (parent) parent.remove();
                });
            }

            container.querySelectorAll('.<?php echo $this->postType; ?>-remove-section-metabox').forEach(btn => {
                attachRemoveEvent(btn);
            });

            // --- Answers
            const answersContainer = document.getElementById('<?php echo $this->postType; ?>-answers-metabox');
            const addAnswerBtn = document.getElementById('add-<?php echo $this->postType; ?>-answer-metabox');
            let answerIndex = <?php echo $answerCount; ?>;

            addAnswerBtn.addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = '<?php echo $this->postType; ?>-answer-metabox';
                div.innerHTML = `
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][text]" placeholder="Answer text" style="width:45%; margin-right:8px;">
                    <input type="text" name="<?php echo $this->postType; ?>_data[answers][${answerIndex}][value]" placeholder="Value" style="width:45%;">
                    <button type="button" class="button <?php echo $this->postType; ?>-remove-answer-metabox">Remove</button>`;
                answersContainer.appendChild(div);
                answerIndex++;
                attachRemoveEvent(div.querySelector('.<?php echo $this->postType; ?>-remove-answer-metabox'));
            });

            answersContainer.querySelectorAll('.<?php echo $this->postType; ?>-remove-answer-metabox').forEach(btn => {
                attachRemoveEvent(btn);
            });
        })();
        </script>
        <?php
    }
}