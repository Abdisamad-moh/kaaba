import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

/**
 * Allows you to dispatch a "modal:close" JavaScript event to close it.
 *
 * This is useful inside a LiveComponent, where you can emit a browser event
 * to open or close the modal.
 *
 * See templates/components/BootstrapModal.html.twig to see how this is
 * attached to Bootstrap modal.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    modal = null;

    connect() {
        this.modal = Modal.getOrCreateInstance(this.element);
        document.addEventListener('modal:close', () => {
            // console.log(this.modal.querySelector('#close-modal'));
            this.modal._element.querySelector('#close-modal').click();
            // this.modal.hide();
        });
    }
}
