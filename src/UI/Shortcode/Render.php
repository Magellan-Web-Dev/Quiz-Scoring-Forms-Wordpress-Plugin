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
                <?= self::renderFormContent() ?>
            </main>
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


                <!-- Answered Questions Section -->
                <section class="answered-questions-sections" id="answered-questions-sections" data-section="answers" :class="{ 'active': currentSection === 'answers' }">
                    <div class="answered-questions-section">
                        <h3>Check Your Answers. Click Edit To Change The Answer</h3>
                        <?php foreach ($data['sections'] as $section) : 
                            $section_id    = esc_attr($section['id']);
                            $section_title = esc_html($section['title']);
                        ?>
                            <div class="answered-questions-section" id="answered-questions-section-<?php echo $section_id; ?>" data-section_answers_item="<?php echo $section_id; ?>">
                                <h3><?php echo $section_title; ?></h3>
                                <ol>
                                    <?php foreach ($section['questions'] as $qIndex => $question) : 
                                        $question_id   = esc_attr($question['id']);
                                        $question_text = esc_html($question['text']);
                                    ?>
                                        <li>
                                            <h4>Question</h4>
                                            <p><?php echo $question_text; ?></p>
                                            <h4>Answer</h4> 
                                            <p 
                                                data_answered_question="<?php echo $question_id; ?>"
                                                x-text="form['question-<?php echo $question_id; ?>'] 
                                                    ? getLabel(form['question-<?php echo $question_id; ?>'], questionValueSchema) 
                                                    : 'Not answered yet'"
                                            ></p>
                                            <button
                                                type="button"
                                                x-on:click="editAnswer('<?php echo $question_id; ?>', <?php echo (int) $qIndex; ?>)"
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
            </form>

            <script>
                // Question Value Schema
                const questionValueSchema = [
                    <?php foreach ($data['scoring'] as $label => $score) : ?>
                        { label: "<?php echo esc_js($label); ?>", score: "<?php echo esc_js($score); ?>" },
                    <?php endforeach; ?>
                ];
            </script>

            <script type="importmap">
                {
                    "imports": {
                        "alpinejs": "/node_modules/alpinejs/dist/module.esm.js"
                    }
                }
            </script>
            <script src="/js/main.js" type="module"></script>
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
                        id="contact-<?php echo $field_id; ?>" 
                        data-contact_item="<?php echo $field_id; ?>" 
                        :class="errors['<?php echo $field_id; ?>'] ? 'field-error' : ''"
                    >
                        <label for="<?php echo $field_id; ?>"><?php echo $field_label; ?>:</label>
                        <input 
                            type="<?php echo $field_type; ?>" 
                            id="<?php echo $field_id; ?>" 
                            name="<?php echo $field_id; ?>" 
                            x-model="form['<?php echo $field_id; ?>']"
                            x-on:input="clearError('<?php echo $field_id; ?>')"
                        >
                        <span x-show="errors['<?php echo $field_id; ?>']" class="field-error-text">
                            <span x-text="errors['<?php echo $field_id; ?>']"></span>
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
                <h3 class="section-title"><?php echo esc_html($this->data->instructions); ?></h3>
                <?php 
                $globalIndex = 0; 
                $sectionStartIndex = 0; 
                foreach ($this->data->schema->getAllQuestionsSections() as $section) :
                    $section_id    = esc_attr($section->id);
                    $section_title = esc_html($section->title);
                    $sectionQuestionsCount = count($section->fields);
                ?>
                    <div 
                        class="questions-section" 
                        id="questions-section-<?php echo $section_id; ?>" 
                        data-section_item="<?php echo $section_id; ?>"
                        :class="{ 'active': 
                            globalQuestionIndex >= <?php echo $sectionStartIndex; ?> && 
                            globalQuestionIndex < <?php echo $sectionStartIndex + $sectionQuestionsCount; ?> 
                        }"
                    >
                        <h4 class="question-section-title"><?php echo $section_title; ?></h4>
                        <?php foreach ($section['questions'] as $index => $question):?>
                            <?= $this->renderQuestionField($question); ?>
                            <?php $globalIndex++; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php $sectionStartIndex += $sectionQuestionsCount; ?>
                <?php endforeach; ?>
            </section>
            <!-- /Questions Sections -->
        <?php
    }

    private function renderQuestionField($question) {
        $question_id   = esc_attr($question['id']);
        $question_text = esc_html($question['text']);
        ?>
            <div 
                class="question-item" 
                id="question-<?php echo $question_id; ?>" 
                data-question_item="<?php echo $question_id; ?>"
                :class="{ 'active': globalQuestionIndex === <?php echo $globalIndex; ?> }"
            >
                <p class="question-heading"><?php echo $question_text; ?></p>
                <div class="question-choices" data-question_choices="<?php echo $question_id; ?>">
                    <?php foreach ($data['scoring'] as $text => $value) : 
                        $choice_label = esc_html($text);
                        $choice_value = esc_attr($value);
                        $choice_id    = 'question-' . $question_id . '-' . $choice_value;
                    ?>
                        <div 
                            class="question-choice" 
                            data-question_choice="<?php echo $question_id . '-' . $choice_value; ?>"
                            x-on:click="chooseAnswer('<?php echo $question_id; ?>', '<?php echo $choice_value; ?>')" 
                        >
                            <input 
                                type="radio" 
                                name="<?php echo $question_id; ?>" 
                                id="<?php echo $choice_id; ?>" 
                                value="<?php echo $choice_value; ?>" 
                                x-ref="radio-<?php echo $choice_id; ?>"
                                x-model="form['question-<?php echo $question_id; ?>']"
                            >
                            <label for="<?php echo $choice_id; ?>"><?php echo $choice_label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <span 
                    x-show="errors['question-<?php echo $question_id; ?>']" 
                    x-text="errors['question-<?php echo $question_id; ?>']" 
                    class="field-error-text">
                </span>
            </div>
        <?php
    }
}