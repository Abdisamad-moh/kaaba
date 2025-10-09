import { Controller } from '@hotwired/stimulus';


export default class extends Controller {
   
    connect() {
        console.log('initialed');
    }

    goToList(event)
    {
        window.location.replace(event.params.url);
    }
    
}