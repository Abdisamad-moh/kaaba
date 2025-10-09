import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import { Toast } from 'bootstrap';
export default class extends Controller {
    static targets = [];
    
    async initialize() {
        this.component = await getComponent(this.element);
    }

    connect()
    {
        console.log('Yoho');
    }
}