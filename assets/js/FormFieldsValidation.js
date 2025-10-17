/**
 * FormFieldsValidation - Class containing functions to validate form fields.
 *
 * @class
 * @returns {Object} - Object containing functions to validate form fields.
 */
export default function FormFieldsValidation() {
    /**
     * Checks if a string contains any Cyrillic or Han characters.
     * @param {string} s - The string to check.
     * @returns {boolean} - True if the string contains any Cyrillic or Han characters, false otherwise.
     */

    const hasCyrillicOrHan = s => {
        /**
         * Unicode property accessor: \p{Script=Cyrillic} matches any Cyrillic character.
         * Unicode property accessor: \p{Script=Han} matches any Han character.
         * The 'u' flag at the end of the regex makes it work with Unicode code points.
         */
        return /[\p{Script=Cyrillic}\p{Script=Han}]/u.test(s);
    };
    return {
        /**
         * isValidEmail - Function to validate an email address.  Prevents Cyrillic and Mandarin characters
         *
         * @param {string} email - Email address to be validated.
         * @returns {boolean} - True if the email address is valid, false otherwise.
         */
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email) && !hasCyrillicOrHan(email);
        },

        /**
         * isValidPhone - Function to validate a phone number.  Prevents Cyrillic and Mandarin characters
         *
         * @param {string} phone - Phone number to be validated.
         * @returns {boolean} - True if the phone number is valid, false otherwise.
         */
        isValidPhone(phone) {
            const re = /^\+?[0-9\s()-]+$/;
            return re.test(phone) && !hasCyrillicOrHan(phone);
        },

        /**
         * isTextValid - Function to validate text.  Prevents Cyrillic and Mandarin characters
         * 
         * @param {string} name 
         * @returns {boolean} - True if the name is valid, false otherwise
         */
        isTextValid(text) {
            const re = /^[A-Za-z0-9_,]+$/u;
            return re.test(text) && !hasCyrillicOrHan(text);
        }
    }
}
