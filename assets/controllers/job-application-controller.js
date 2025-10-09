import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'reportDiv']
    connect() {

        document.addEventListener('application:submitted', () => {
            const form = this.formTarget;
            // this.formTarget.querySelector('#apply_button').disabled = true;
            form.submit();
        });
    }

    showReportForm()
    {
        this.reportDivTarget.classList.remove('d-none');
        this.reportDivTarget.querySelector('#description').focus();
    }
}