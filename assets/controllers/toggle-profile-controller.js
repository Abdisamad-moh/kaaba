import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["checkbox"];

    connect()
    {
        console.log('connected');
    }

    toggle(event) {
        event.preventDefault();

        // Ask for confirmation
        const confirmed = confirm("Are you sure you want to update this setting?");
        if (!confirmed) {
            // If the user cancels, revert the toggle state
            event.target.checked = !event.target.checked;
            return;
        }

        // Get data from the element's attributes
        const url = event.target.dataset.toggleUrl;
        const csrfToken = event.target.dataset.toggleCsrf;
        const field = event.target.dataset.toggleField;  // The field to update (e.g., 'resumeVisibility', 'publicProfile')
        const value = event.target.checked ? 1 : 0;

        // Make the AJAX request
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({ field: field, value: value })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("There was an issue updating the field.");
                // Revert toggle if there was an error
                event.target.checked = !event.target.checked;
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
            event.target.checked = !event.target.checked;
        });
    }
}
