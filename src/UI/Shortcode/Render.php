<?php

declare(strict_types=1);

namespace QuizScoringForms\Ui\Shortcode;

use QuizScoringForms\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

final class Render 
{

    private int $globalIndex = 0;

    public function __construct(
        public readonly object $data
    ) {}

    /**
     * Render error message if shortcode data could not be found
     */
    public static function renderErrMsg($msg): string 
    {
        return '<h3 class="'.Config::PLUGIN_ABBREV.'-err-msg err-msg">'. esc_html($msg).'</h3>';
    }

    public function renderContent(): void 
    {
        ?>
            <main class="<?=Config::PLUGIN_ABBREV?>-main-container">
                <?= $this->renderFormContent(); ?>
            </main>
            <script>
                // Question Value Schema
                const questionValueSchema = [
                    <?php foreach ($this->data->answers as $answer) : ?>
                        { text: "<?= esc_js($answer['text']); ?>", value: "<?= esc_js($answer['value']); ?>" },
                    <?php endforeach; ?>
                ];
            </script>
            <script type="importmap">
                {
                    "imports": {
                        "alpinejs": "<?= Config::ASSETS_PATH; ?>/node_modules/alpinejs/dist/module.esm.js"
                    }
                }
            </script>
            <script src="<?= Config::ASSETS_PATH; ?>/js/main.js" type="module"></script>
        <?php
    }

    private function renderFormContent() {
        ?>
            <form 
                method="POST" 
                id="quiz-form" 
                novalidate
                x-data="formHandler(questionValueSchema)" 
                x-on:submit.prevent="validateSection"
                x-init="<?php if (!empty($hasErrors)) : ?>
                    $nextTick(() => validateAll({ target: $root }, true))
                <?php endif; ?>"
            >

            <?= $this->renderContactSection() ?>
            <?= $this->renderQuestionSections() ?>
            </form>
        <?php
    }

    /**
     * Renders the contact section
     * 
     * @return void
     */
    private function renderContactSection(): void {
        ?>
            <!-- Contact Section -->
            <section class="contact-section" id="contact-section" data-section="contact" :class="{ 'active': currentSection === 'contact' }">
                <h3 class="section-title">Fill In Your Contact Information</h3>
                <?php foreach ($this->data->schema->getAllContactFields() as $field) :
                    $field_id    = esc_attr($field->id);
                    $field_label = esc_html($field->label);
                    $field_type  = esc_attr($field->htmlType);
                ?>
                    <div 
                        class="contact-item" 
                        id="contact-<?= $field_id; ?>" 
                        data-contact_item="<?= $field_id; ?>" 
                        :class="errors['<?= $field_id; ?>'] ? 'field-error' : ''"
                    >
                        <label for="<?= $field_id; ?>"><?= $field_label; ?>:</label>
                        <input 
                            type="<?= $field_type; ?>" 
                            id="<?= $field_id; ?>" 
                            name="<?= $field_id; ?>" 
                            x-model="form['<?= $field_id; ?>']"
                            x-on:input="clearError('<?= $field_id; ?>')"
                        >
                        <span x-show="errors['<?= $field_id; ?>']" class="field-error-text">
                            <span x-text="errors['<?= $field_id; ?>']"></span>
                        </span>
                    </div>
                <?php endforeach; ?>
                <button type="button" x-on:click="validateSection($event)">Go To Questions</button>
            </section>
            <!-- /Contact Section -->
        <?php
    }

    /**
     * Renders the question sections
     * 
     * @return void
     */

    private function renderQuestionSections() {
        ?>
            <!-- Questions Sections -->
            <section class="questions-sections" id="questions-sections" data-section="questions" :class="{ 'active': currentSection === 'questions' }">
                <h3 class="section-title"><?= esc_html($this->data->instructions); ?></h3>
                <?php 
                    $sectionStartIndex = 0;
                    foreach ($this->data->schema->getAllQuestionsSections() as $section) :
                        $section_id    = esc_attr($section->id);
                        $section_title = esc_html($section->title);
                        $sectionQuestionsCount = count($section->fields);
                    ?>
                        <div 
                            class="questions-section" 
                            id="questions-section-<?= $section_id; ?>" 
                            data-section_item="<?= $section_id; ?>"
                            :class="{ 'active': 
                                globalQuestionIndex >= <?= $sectionStartIndex; ?> && 
                                globalQuestionIndex < <?= $sectionStartIndex + $sectionQuestionsCount; ?> 
                            }"
                        >
                            <h4 class="question-section-title"><?= $section_title; ?></h4>
                            <?php foreach ($section->fieldsData as $question):?>
                                <?= $this->renderQuestionField($question); ?>
                                <?php $this->globalIndex++; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php $sectionStartIndex += $sectionQuestionsCount; ?>
                <?php endforeach; ?>
            </section>
            <!-- /Questions Sections -->
        <?php
    }

    private function renderQuestionField($question) {
        var_dump($question);
        $question_id   = esc_attr($question->id);
        $question_text = esc_html($question->label);
        ?>
            <div 
                class="question-item" 
                id="question-<?= $question_id; ?>" 
                data-question_item="<?= $question_id; ?>"
                :class="{ 'active': globalQuestionIndex === <?= $this->globalIndex; ?> }"
            >
                <p class="question-heading"><?= $question_text; ?></p>
                <div class="question-choices" data-question_choices="<?= $question_id; ?>">
                    <?php foreach ($data['scoring'] as $text => $value) : 
                        $choice_label = esc_html($text);
                        $choice_value = esc_attr($value);
                        $choice_id    = 'question-' . $question_id . '-' . $choice_value;
                    ?>
                        <div 
                            class="question-choice" 
                            data-question_choice="<?= $question_id . '-' . $choice_value; ?>"
                            x-on:click="chooseAnswer('<?= $question_id; ?>', '<?= $choice_value; ?>')" 
                        >
                            <input 
                                type="radio" 
                                name="<?= $question_id; ?>" 
                                id="<?= $choice_id; ?>" 
                                value="<?= $choice_value; ?>" 
                                x-ref="radio-<?= $choice_id; ?>"
                                x-model="form['question-<?= $question_id; ?>']"
                            >
                            <label for="<?= $choice_id; ?>"><?= $choice_label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <span 
                    x-show="errors['question-<?= $question_id; ?>']" 
                    x-text="errors['question-<?= $question_id; ?>']" 
                    class="field-error-text">
                </span>
            </div>
        <?php
    }

    private function renderAnsweredQuestionsSection($data) {
        ?>
            <!-- Answered Questions Section -->
            <section class="answered-questions-sections" id="answered-questions-sections" data-section="answers" :class="{ 'active': currentSection === 'answers' }">
                <div class="answered-questions-section">
                    <h3>Check Your Answers. Click Edit To Change The Answer</h3>
                    <?php foreach ($data['sections'] as $section) : 
                        $section_id    = esc_attr($section['id']);
                        $section_title = esc_html($section['title']);
                    ?>
                        <div class="answered-questions-section" id="answered-questions-section-<?= $section_id; ?>" data-section_answers_item="<?= $section_id; ?>">
                            <h3><?= $section_title; ?></h3>
                            <ol>
                                <?php foreach ($section['questions'] as $qIndex => $question) : 
                                    $question_id   = esc_attr($question['id']);
                                    $question_text = esc_html($question['text']);
                                ?>
                                    <li>
                                        <h4>Question</h4>
                                        <p><?= $question_text; ?></p>
                                        <h4>Answer</h4> 
                                        <p 
                                            data_answered_question="<?= $question_id; ?>"
                                            x-text="form['question-<?= $question_id; ?>'] 
                                                ? getLabel(form['question-<?= $question_id; ?>'], questionValueSchema) 
                                                : 'Not answered yet'"
                                        ></p>
                                        <button
                                            type="button"
                                            x-on:click="editAnswer('<?= $question_id; ?>', <?= (int) $qIndex; ?>)"
                                        >
                                            Edit
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" x-on:click="validateAll($event)">Submit</button>
            </section>
        <!-- /Answered Questions Section -->
        <?php
    }
}