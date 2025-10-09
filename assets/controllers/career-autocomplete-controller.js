import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';

export default class extends Controller {
    static targets = ['careerInput'];
    static values = {
        careerUrl: String,
    }
    educationModal;

    connect() {
        
        if(this.careerInputTarget)
        {
            const  element = new TomSelect(this.careerInputTarget, {
                create: true,
                maxItems: 1,
                load: (query, callback) => {
                    fetch(`${this.careerUrlValue}?search=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            callback(data);
                        }).catch(() => {
                            callback();
                        });
                },
            });
        }

    }

    fetchOptions(careerId, query, callback)
    {
        fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent(query)}&careerId=${careerId}`)
            .then(response => response.json())
            .then(data => {
                callback(data);
            }).catch(() => {
                callback();
            });
    }

    scrollToTop() {
        this.modalTarget.scrollTo({
            top: 0,
            behavior: 'smooth'
        });  // Set the scroll position of the modal body to the top
    }


    
}