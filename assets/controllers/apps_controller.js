import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // static targets = ['infoForm']
    connect() {
        // const form = this.infoFormTarget;
        console.log('connected');
    }

    jumpNext(event) {
        // const digitInputs = document.querySelectorAll('.digit-input');
        const element = event.target;
        if (element.value.length === element.maxLength) {
            const nextInput = element.nextElementSibling;
            if (nextInput) {
              nextInput.focus();
            }
          }
    }
}