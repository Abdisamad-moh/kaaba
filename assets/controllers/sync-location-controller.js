import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';

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

        const country_input = this.element.querySelector('.country_input');
        const city_input = this.element.querySelector('.city_input');
        const state_input = this.element.querySelector('.state_input');
        
        if(city_input)
        {
            const cityTomSelect = new TomSelect(city_input, {
                maxItems: 1,
                valueField: 'text', // The property that TomSelect should use as the value
                labelField: 'text',  // The property that TomSelect should display as the label
                load: (query, callback) => {
                    this.fetchCities(query, country_input.value, state_input?.value ?? '').then(data => {
                        callback(data);
                    })
                    .catch(err => {
                        callback();
                    });
                },
            });

            city_input.addEventListener('change', () => {
                if(!state_input) return;

                this.fetchStates('', country_input.value, city_input?.value ?? '').then(data => {
                    let tomSelectInstance = state_input.tomselect;
                    tomSelectInstance.clear();
                    tomSelectInstance.clearOptions();
                    tomSelectInstance.addOptions(data);
                    // if(data[0].length === 1)
                    // {
                    //     console.log(data[0][0].value)
                    //     tomSelectInstance.addItem(data[0][0].text);
                    // }
                });

            })

            if(state_input)
            {
                this.fetchStates('', country_input.value, city_input?.value ?? '').then(data => {
                    let tomSelectInstance = state_input.tomselect;
                    tomSelectInstance.clear();
                    tomSelectInstance.clearOptions();
                    tomSelectInstance.addOptions(data);
                    if(data[0].length === 1)
                    {
                        console.log(data[0][0].value)
                        tomSelectInstance.addItem(data[0][0].text);
                    }
                });
            }
        }

        if(state_input)
        {
            const stateTomSelect = new TomSelect(state_input, {
                maxItems: 1,
                valueField: 'text', // The property that TomSelect should use as the value
                labelField: 'text',  // The property that TomSelect should display as the label
                load: (query, callback) => {
                    this.fetchStates(query, country_input.value).then(data => {
                        callback(data);
                    })
                    .catch(err => {
                        callback();
                    });
                },
            });
        }
        
        if(country_input)
        {
            country_input.addEventListener('change', () => {
                this.fetchStates('', country_input.value).then(data => {
                    let tomSelectInstance = state_input.tomselect;
                        tomSelectInstance.clear();
                        tomSelectInstance.clearOptions();
                        tomSelectInstance.addOptions(data);
                });

                this.fetchCities('', country_input.value, '').then(data => {
                    let tomSelectInstance = city_input.tomselect;
                        tomSelectInstance.clear();
                        tomSelectInstance.clearOptions();
                        tomSelectInstance.addOptions(data);
                });
            })
        }

        // if(state_input)
        // {
        //     state_input.addEventListener('change', () => {
        //         this.fetchCities('', country_input.value, state_input?.value ?? '').then(data => {
        //             let tomSelectInstance = city_input.tomselect;
        //                 tomSelectInstance.clear();
        //                 tomSelectInstance.clearOptions();
        //                 tomSelectInstance.addOptions(data);
        //         });
        //     })
        // }

        
        this.fetchStates('', country_input.value).then(data => {
            let tomSelectInstance = state_input.tomselect;
                tomSelectInstance.clearOptions();
                tomSelectInstance.addOptions(data);
        });

        this.fetchCities('', country_input.value, state_input?.value ?? '').then(data => {
            let tomSelectInstance = city_input.tomselect;
                tomSelectInstance.clearOptions();
                tomSelectInstance.addOptions(data);
        });
       
    }

    fetchCities(searchTerm = '', country = '', state = '')
    {
        let url = `${this.citiesUrlValue}?searchTerm=${encodeURIComponent(searchTerm)}&country=${encodeURIComponent(country)}&state=${encodeURIComponent(state)}`;
        return axios.get(url)
            .then(response => response.data) // Axios automatically handles JSON data parsing
            .catch(error => {
                console.error('Error fetching data:', error);
                return []; // Return an empty array in case of error
            });
    }

    fetchStates(searchTerm = '', country = '', city = '')
    {
        let url = `${this.statesUrlValue}?searchTerm=${encodeURIComponent(searchTerm)}&country=${encodeURIComponent(country)}&city=${encodeURIComponent(city)}`;
        return axios.get(url)
            .then(response => response.data) // Axios automatically handles JSON data parsing
            .catch(error => {
                console.error('Error fetching data:', error);
                return []; // Return an empty array in case of error
            });
    }
    
}