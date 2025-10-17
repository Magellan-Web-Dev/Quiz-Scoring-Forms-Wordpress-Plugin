import Alpine from 'alpinejs';
import FormHandler from './FormHandler.js';

window.Alpine = Alpine;

Alpine.data("formHandler", FormHandler);

Alpine.start();