import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'content'];

    toggle(event) {
        const button = event.currentTarget; // Get the clicked button
        const targetId = button.getAttribute('data-bs-target'); // Find the target collapse panel by its ID
        const content = document.querySelector(targetId); // Select the corresponding collapse content
        
        // Check if the content is open or closed
        if (content.classList.contains('show')) {
            // Manually close it if open
            content.classList.remove('show');
            button.classList.add('collapsed');
            button.setAttribute('aria-expanded', 'false');
        } else {
            // Manually open it if closed
            content.classList.add('show');
            button.classList.remove('collapsed');
            button.setAttribute('aria-expanded', 'true');
        }
    }
}