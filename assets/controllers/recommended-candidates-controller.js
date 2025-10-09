import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
import TomSelect from 'tom-select';
import Choices from 'choices.js';

export default class extends Controller {
    static targets = [];
    static values = {
        url: String,
        citiesUrl: String,
        offset: Number,
        limit: Number,
    }
    connect() {
        // this.checkJobAndAdjustCountry();
        
    }

    jobChanged(event)
    {
        // let country_input = this.element.querySelector('.country_input');
        // const selectedOption = event.target.selectedOptions[0];
        // const localsOnly = selectedOption.dataset.isLocalsOnly == 'true';

        // country_input.tomselect.clear();
        // country_input.tomselect.disable();
        this.checkJobAndAdjustCountry();
    }

    checkJobAndAdjustCountry() {
        let country_input = this.element.querySelector('.country_input');
        const selectedOption = this.element.querySelector('.job_input').selectedOptions[0];
        const isLocalsOnly = selectedOption.dataset.isLocalsOnly === 'true';

        // Clear and disable the country input if the job is marked as locals-only
        if (isLocalsOnly) {
            country_input.tomselect.clear();
            country_input.tomselect.disable();
        } else {
            country_input.tomselect.enable();
        }
    }

}