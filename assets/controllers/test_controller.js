import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element);

        this.component.on('render:finished', (component) => {
            this.enableSelect2();
        });
    }

    enableSelect2() {
        // Assuming all select elements need Select2, you can target them with a selector
        $('select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2(); // Initialize Select2 if not already initialized
            }
        });
    }
}
