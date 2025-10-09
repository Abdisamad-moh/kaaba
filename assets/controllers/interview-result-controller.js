import { Controller } from '@hotwired/stimulus';


export default class extends Controller {
    static targets = [];
    static values = {
        citiesUrl: String,
        statesUrl: String,
        city: Number,
        state: Number,
    }

    educationModal;

    connect() {

       
    }

    change(event)
    {
        if(event.target.value == 'next round')
        {
            Array.from(this.element.querySelectorAll('.nex_round_hide')).forEach((item) => { 
                item.disabled = false;
            });

        } else {
          

            Array.from(this.element.querySelectorAll('.nex_round_hide')).forEach((item) => { 
                item.value = '';
                item.disabled = true;
            
            });
        }
    }
    
}