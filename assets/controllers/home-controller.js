import { Controller } from '@hotwired/stimulus';
// import { Modal } from 'bootstrap';
// import axios from 'axios';

export default class extends Controller {
    static targets = ['carousel01', 'carousel02', 'carousel03', 'carousel04', 'carousel05', 'carousel'];

    connect() {

    }

    carouselChange(event)
    {
        event.preventDefault();
        const active = event.target.dataset.carousel;

        this.carouselTargets.forEach((carousel) => {
            if(carousel.dataset.carousel == active) 
                carousel.classList.add('active');
            else 
                carousel.classList.remove('active');
        });
        
    }
    
}