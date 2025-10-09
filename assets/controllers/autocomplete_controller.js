// assets/controllers/autocomplete_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["qaForm"];

    select(event) {
        const jobTitleId = event.detail.choice.value;
        // Update the live component with the selected job title ID
        fetch(`/live-component/qa?jobTitleId=${jobTitleId}`, {
            headers: { 'Accept': 'application/vnd.live-component+html' }
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('interview-questions').innerHTML = html;
        });
    }
}
