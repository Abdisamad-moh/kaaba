import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
import TomSelect from 'tom-select';
import Choices from 'choices.js';

export default class extends Controller {
    static targets = ['title', 'country', 'city', , 'loadMore', 'list'];
    static values = {
        url: String,
        citiesUrl: String,
        offset: Number,
        limit: Number,
    }
    connect() {
        this.offsetValue = this.offsetValue || 0;
        this.limitValue = this.limitValue || 20;
        new TomSelect(this.countryTarget, {});

        const cityChoices = new TomSelect(this.cityTarget, {
            load: (query, callback) => {
                this.fetchCities(query).then(data => {
                    callback(data);
                })
                .catch(err => {
                    callback();
                });
            },
        });

        this.applyFilter();

        this.countryTarget.addEventListener('change', (event) => {
            this.fetchCities().then(data => {
                let tomSelectInstance = this.cityTarget.tomselect;
                    tomSelectInstance.clear();
                    tomSelectInstance.clearOptions();
                    tomSelectInstance.addOptions(data);
            });
        })

        this.fetchCities().then(data => {
            let tomSelectInstance = this.cityTarget.tomselect;
                tomSelectInstance.clearOptions();
                tomSelectInstance.addOptions(data);
        });

    }

    applyFilter(loadMore = false) {
        if(loadMore !== true) {
            this.offsetValue = 0;
            this.loaderEffect(true);
        }
        
        const params = new URLSearchParams({
            offset: this.offsetValue,
            limit: this.limitValue,
            title: this.titleTarget.value,
            country: this.countryTarget.value,
            city: this.cityTarget.value,
        });

        axios.get(`${this.urlValue}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            this.removeLoaderEffect();
            this.offsetValue = this.offsetValue + this.limitValue;

            if(loadMore === true) {
                this.listTarget.insertAdjacentHTML('beforeend', response.data.html);

            } else {
                // this.offsetValue = this.offsetValue + this.limitValue;
                this.listTarget.innerHTML = response.data.html;
            }

            this.loadMoreTarget.querySelector('span').innerText = response.data.remaining > 0 ? `Load More (${response.data.remaining})` : 'No More Records Found';
        })
        .catch(error => {
            console.error('Sorry, something went wrong', error);
        });
    }

    loadMore(event)
    {
        this.loaderEffect();
        event.preventDefault();
        this.applyFilter(true);
    }

    fetchCities(searchTerm = null)
    {
        let url = `${this.citiesUrlValue}?searchTerm=${encodeURIComponent(searchTerm)}&country=${encodeURIComponent(this.countryTarget.value)}`;
        return axios.get(url)
            .then(response => response.data) // Axios automatically handles JSON data parsing
            .catch(error => {
                console.error('Error fetching data:', error);
                return []; // Return an empty array in case of error
            });
    }

    loaderEffect(fullLoad = false)
    {
        let loading_card = '';
        for(let i = 0; i < 3; i++) {
            loading_card += 
            `<div class="col-lg-4 col-md-6 mt-4 loader-item">
                <div class="card job-grid-box">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-8">
                                <p class="card-text placeholder-glow">
                                <span class="placeholder col-7 placeholder-lg" style="height: 54px; width: 30%"></span>
                                </p>
                            </div>
                            <div class="col-4">
                                <p class="card-text placeholder-glow">
                                <span class="placeholder col-7"></span>
                                <span class="placeholder col-4"></span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="card-text placeholder-glow">
                            <span class="placeholder col-7 placeholder-lg"></span>
                            <span class="placeholder col-4 placeholder-lg"></span>
                            <span class="placeholder col-4 placeholder-lg"></span>
                            </p>
                        </div>
                        <div class="job-grid-content mt-2">
                            <p class="card-text placeholder-glow">
                                <span class="placeholder col-7 placeholder-xs"></span>
                                <span class="placeholder col-4 placeholder-xs"></span>
                                <span class="placeholder col-4 placeholder-xs"></span>
                                <span class="placeholder col-6 placeholder-xs"></span>
                                <span class="placeholder col-8 placeholder-xs"></span>
                            </p>
                            <ul class="list-inline py-3">
                            </ul>
                            <div class="row">
                                <div class="col-8">
                                    <p class="card-text placeholder-glow">
                                        <span class="placeholder col-7 "></span>
                                        <span class="placeholder col-4 "></span>
                                    </p>
                                </div>
                                <div class="col-4">
                                    <p class="card-text placeholder-glow">
                                        <span class="placeholder col-7 "></span>
                                    
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>`;

        }

        if(fullLoad === true) {
            this.listTarget.innerHTML = loading_card;
        } else {
            this.listTarget.insertAdjacentHTML('beforeend', loading_card);
        }
    }

    removeLoaderEffect()
    {
        this.listTarget.querySelectorAll('.loader-item').forEach(job => job.remove());
    }

    
}