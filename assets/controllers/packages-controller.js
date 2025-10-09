import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static targets = ['search', 'packages', 'loadMore', 'more'];
    static values = {
        url: String,
        search: String,
        offset: Number,
        limit: Number,
    }
    connect() {
        
        this.offsetValue = this.offsetValue || 16;
        this.limitValue = this.limitValue || 16;
        
        this.searchTarget.addEventListener('keyup', this.debounce((event) => {
            this.offsetValue = 0;
            this.applyFilter();
        }, 300)); // Adjust 300ms to the desired delay

        this.applyFilter();

    }

    applyFilter(loadMore = false) {
        if(loadMore !== true) {
            this.offsetValue = 0;
            this.loaderEffect(true);
        }
        const params = new URLSearchParams({
            offset: this.offsetValue,
            limit: this.limitValue,
            search: this.searchTarget.value,
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
                this.packagesTarget.insertAdjacentHTML('beforeend', response.data.html);

            } else {
                // this.offsetValue = this.offsetValue + this.limitValue;
                this.packagesTarget.innerHTML = response.data.html;
            }
            
            const load_more_element = this.element.querySelector('#load_more').closest('div');

            if(response.data.number_of_records < 16)
            {
                // add d-none to the more target
                load_more_element.classList.add('d-none');
            } else {
                load_more_element.classList.remove('d-none');
            }
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
        event.target.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' // or 'start', 'end', depending on your preference
          });
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

    debounce(func, delay) {
        let timeoutId;
    
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
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
            this.packagesTarget.innerHTML = loading_card;
        } else {
            this.packagesTarget.insertAdjacentHTML('beforeend', loading_card);
        }
    }

    removeLoaderEffect()
    {
        this.packagesTarget.querySelectorAll('.loader-item').forEach(job => job.remove());
    }

}