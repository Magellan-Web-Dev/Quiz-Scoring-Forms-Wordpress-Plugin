import FormFieldsValidation from './FormFieldsValidation.js';

const FormFieldsValidator = FormFieldsValidation();

// FormHandler.js

export default function FormHandler(questionValueSchema = []) {
    return {
        form: {}, // stores all form fields (contact + questions)
        errors: {}, // validation errors per field
        currentSection: 'contact', // active section
        currentQuestionIndex: 0, // index within current section
        globalQuestionIndex: 0, // overall question index
        editingQuestionId: null, // question being edited
        sections: ['contact', 'questions', 'answers'], // available sections
        questionValueSchema, // question scoring schema,
        transitioning: false,

        // Initialize form and restore saved state
        init() {
            let restored = false;
            const saved = localStorage.getItem(LOCAL_STORAGE_NAME);
        
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    if (parsed.expiresAt && Date.now() < parsed.expiresAt) {
                        Object.entries(parsed.answers).forEach(([key, value]) => {
                            this.form[key] = value;
                        });
                        this.currentSection = parsed.currentSection || 'contact';
                        this.currentQuestionIndex = parsed.currentQuestionIndex || 0;
        
                        // Only restore globalQuestionIndex if valid
                        const totalQuestions = this.$root.querySelectorAll('[data-question_item]').length;
                        if (parsed.globalQuestionIndex >= 0 && parsed.globalQuestionIndex < totalQuestions) {
                            this.globalQuestionIndex = parsed.globalQuestionIndex;
                        }
        
                        restored = true;
                    } else {
                        localStorage.removeItem(LOCAL_STORAGE_NAME);
                    }
                } catch (e) {
                    console.warn("Invalid localStorage, clearing...");
                    localStorage.removeItem(LOCAL_STORAGE_NAME);
                }
            }
        
            // Initialize all x-model inputs
            this.$root.querySelectorAll("[x-model]").forEach(el => {
                const match = el.getAttribute("x-model").match(/form\[['"](.+?)['"]\]/);
                if (!match) return;
                const fieldName = match[1];
                if (!(fieldName in this.form)) this.form[fieldName] = "";
        
                const value = this.form[fieldName];
                if (el.type === "radio") el.checked = el.value == value;
                else if (el.type === "checkbox") el.checked = !!value;
                else el.value = value;
            });
        
            // If nothing was restored, set first unanswered question
            if (!restored && this.currentSection === 'questions') {
                const questionItems = this.$root.querySelectorAll("[data-question_item]");
                for (let i = 0; i < questionItems.length; i++) {
                    const qid = questionItems[i].dataset.question_item;
                    if (!this.form[`question-${qid}`] || this.form[`question-${qid}`] === "") {
                        this.globalQuestionIndex = i;
                        break;
                    }
                }
            }
        
            // Watch changes and persist
            this.$watch("form", () => {
                this.saveAnswers();
        
                this.$root.querySelectorAll("[x-model]").forEach(el => {
                    const match = el.getAttribute("x-model").match(/form\[['"](.+?)['"]\]/);
                    if (!match) return;
                    const fieldName = match[1];
                    const value = this.form[fieldName];
        
                    if (el.type === "radio") el.checked = el.value == value;
                    else if (el.type === "checkbox") el.checked = !!value;
                    else el.value = value;
                });
            });
        },

        // Persist form to localStorage
        saveAnswers() {
            const answers = { ...this.form };
            const data = {
                expiresAt: Date.now() + 10 * 60 * 1000,
                answers,
                currentSection: this.currentSection,
                currentQuestionIndex: this.currentQuestionIndex,
                globalQuestionIndex: this.globalQuestionIndex
            };
            localStorage.setItem(LOCAL_STORAGE_NAME, JSON.stringify(data));
        },

        // Move to next question in sequence
        nextQuestion() {
            const totalQuestions = this.$root.querySelectorAll('[data-question_item]').length;
            if (this.globalQuestionIndex < totalQuestions - 1) {
                this.globalQuestionIndex++;
            } else {
                this.currentSection = 'answers';
            }
        },

        // Move to previous question
        prevQuestion() {
            if (this.globalQuestionIndex > 0) this.globalQuestionIndex--;
        },

        // Go to a specific question
        goToQuestion(index) {
            this.globalQuestionIndex = index;
        },

        // Check if all questions have been answered
        allQuestionsAnswered() {
            const allQuestionElements = this.$root.querySelectorAll('[data-question_item]');
            let allAnswered = true;
        
            allQuestionElements.forEach(el => {
                const qid = el.dataset.question_item;
                const key = `question-${qid}`;
                if (!this.form[key] || this.form[key] === "") allAnswered = false;
            });

            return allAnswered;
        },

        // Select an answer.  Add slight delay to prevent rapid clicking
        chooseAnswer(questionId, value) {
            if (this.transitioning) return;
            this.transitioning = true;
            setTimeout(() => {
                const key = `question-${questionId}`;
        
                // If editing and user re-clicked the same answer, just finish editing
                if (this.editingQuestionId === questionId && this.form[key] == value) {
                    this.finishEdit();
                    this.transitioning = false;
                    return;
                }
            
                // Assign the value
                this.form[key] = value;
                this.clearError(key);
            
                // Decide where to go next
                if (this.allQuestionsAnswered()) {
                    this.currentSection = 'answers';
                } else {
                    this.nextQuestion();
                }
            
                this.saveAnswers();
                this.transitioning = false;
            }, 50);
        },

        // Edit an answer
        editAnswer(questionId, index) {
            this.editingQuestionId = questionId;
        
            // Find the global index of this question
            const allQuestions = Array.from(this.$root.querySelectorAll('[data-question_item]'));
            const questionEl = allQuestions.find(el => el.dataset.question_item == questionId);
            if (!questionEl) return;
        
            this.globalQuestionIndex = allQuestions.indexOf(questionEl);
        
            // Find the parent section of this question
            const sectionEl = questionEl.closest('[data-section_item]');
            if (sectionEl) {
                const sectionId = sectionEl.dataset.section_item;
                const allSections = Array.from(this.$root.querySelectorAll('[data-section_item]'));
                const sectionIndex = allSections.indexOf(sectionEl);
                // Optional: you could store current section as index or id
                this.currentSection = 'questions'; // the main questions container
                // You can also store which sub-section is active if you need finer control
            }
        },

        // Finish editing an answer
        finishEdit() {
            this.currentSection = 'answers'; // immediately switch to answers section
            this.editingQuestionId = null;   // reset editing ID after switching
        },

        // Go to the next section
        nextSection() {
            const currentIndex = this.sections.indexOf(this.currentSection);
            if (currentIndex < this.sections.length - 1) {
                this.currentSection = this.sections[currentIndex + 1];
                if (this.currentSection === 'questions') this.globalQuestionIndex = 0;
            }
        },

        // Validate inputs of current section
        validateSection(event) {
            this.errors = {};
            const activeSection = this.$root.querySelector(`[data-section="${this.currentSection}"]`);

            if (this.currentSection === 'contact') {
                activeSection.querySelectorAll("input[x-model]").forEach(input => {
                    const key = input.getAttribute("x-model").match(/form\[['"](.+?)['"]\]/)[1];
                    const value = this.form[key];

                    if (!value) this.errors[key] = "This field is required";
                    if (key === 'email' && value && !FormFieldsValidator.isValidEmail(value)) this.errors[key] = "Please enter a valid email address";
                    if (key === 'phone' && value && !FormFieldsValidator.isValidPhone(value)) this.errors[key] = "Please enter a valid phone number";
                });
            }

            if (this.currentSection === 'questions') {
                activeSection.querySelectorAll("[data-question_item]").forEach(el => {
                    const qid = el.dataset.question_item;
                    const fieldName = `question-${qid}`;
                    if (!this.form[fieldName] || this.form[fieldName] === "") this.errors[fieldName] = "Please select an answer";
                });
            }

            if (Object.keys(this.errors).length === 0) {
                if (this.currentSection !== 'answers') this.nextSection();
            } else if (IS_DEV) {
                console.log("Validation errors:", this.errors);
            }
        },

        // Validate all sections before final submission
        validateAll(event, errorOnLoad = false) {
            this.errors = {};

            this.sections.forEach(section => {
                const sectionEl = this.$root.querySelector(`[data-section="${section}"]`);
                if (!sectionEl) return;

                if (section === 'contact') {
                    sectionEl.querySelectorAll("input[x-model]").forEach(input => {
                        const key = input.getAttribute("x-model").match(/form\[['"](.+?)['"]\]/)[1];
                        const value = this.form[key];
                        if (!value) this.errors[key] = "This field is required";
                        if (key === 'email' && value && !FormFieldsValidator.isValidEmail(value)) this.errors[key] = "Please enter a valid email address";
                        if (key === 'phone' && value && !FormFieldsValidator.isValidPhone(value)) this.errors[key] = "Please enter a valid phone number";
                    });
                }

                if (section === 'questions') {
                    sectionEl.querySelectorAll("[data-question_item]").forEach(el => {
                        const qid = el.dataset.question_item;
                        const fieldName = `question-${qid}`;
                        if (!this.form[fieldName]) this.errors[fieldName] = "Please select an answer";
                    });
                }
            });

            // Go to answers section if all questions have been answered and if a server threw an error
            if (this.allQuestionsAnswered() && errorOnLoad) {
                this.currentSection = 'answers';
                return;
            }

            if (Object.keys(this.errors).length === 0) {
                this.$root.closest("form").submit();
            } else if (IS_DEV) {
                console.log("Final validation errors:", this.errors);
            }

            // Jump to section with first error
            const firstErrorField = Object.keys(this.errors)[0];
            if (firstErrorField.startsWith("question-")) this.currentSection = 'questions';
            else this.currentSection = 'contact';
        },

        getLabel(score) {
            const match = this.questionValueSchema.find(item => item.score == score);
            return match ? match.label : null;
        },

        // Clear an error
        clearError(fieldName) {
            if (this.errors[fieldName]) delete this.errors[fieldName];
        }
    };
}