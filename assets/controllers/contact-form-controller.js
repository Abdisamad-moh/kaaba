import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static values = {
        url: String,
    }
    educationModal;

    connect() {
        this.loadForm();
    }

    loadForm() {
        axios.get(this.urlValue)
            .then((response) => {
                const form_container = this.element.querySelector('.form_container');
                form_container.innerHTML = response.data;
                this.element.querySelector('form').action = this.urlValue;
                this.addFormSubmitListener(this.element.querySelector('form'));
            })
            .catch(error => console.error('sorry, something went wrong', error));
    }

    addFormSubmitListener(form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            const url = form.action;
            axios.post(url, formData)
                .then(response => {
                    this.handleFormSuccess(response);
                })
                .catch(error => {
                    this.handleFormError(error);
                });
        });
    }

    handleFormSuccess(response) {
        this.loadForm();

        let alert = document.createElement('div');
        alert.classList.add('alert', 'alert-success');
        alert.textContent = 'Thank you for your message!';
        console.log(this.element.querySelector('.alert_message'));
        this.element.querySelector('.alert_message').appendChild(alert);
        // this.element.insertBefore(alert, this.element);
    }

    handleFormError(error) {
        console.error('Form submission error:', error);

        if (error.response && error.response.status === 422) {
            let alert = document.createElement('div');
            alert.classList.add('alert', 'alert-danger');
            alert.textContent = 'Sorry, something went wrong. Please try again.';
            this.element.querySelector('.alert_message').appendChild(alert);

        } else {
            // this.showMessage('An unexpected error occurred.', 'danger');
            console.log(console.log(error));
        }
    }
    
    
}