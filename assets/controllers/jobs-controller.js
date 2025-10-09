import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
import TomSelect from 'tom-select';
import Choices from 'choices.js';

export default class extends Controller {
    static targets = ['title', 'country', 'experience', 'city', 'salary', 'jobType', 'jobs', 'loadMore', 'immediateHiring', 'jobCategory', 'education', 'postedDate'];
    static values = {
        url: String,
        citiesUrl: String,
        offset: Number,
        limit: Number,
    }
    connect() {
        this.offsetValue = this.offsetValue || 15;
        this.limitValue = this.limitValue || 15;
    
        if (this.hasCountryTarget) {
            new TomSelect(this.countryTarget, {});
        }
    
        if (this.hasJobTypeTarget) {
            new TomSelect(this.jobTypeTarget, {});
        }
    
        if (this.hasCityTarget) {
            const cityChoices = new TomSelect(this.cityTarget, {
                plugins: ['clear_button'],
                load: (query, callback) => {
                    this.fetchCities(query).then(data => {
                        callback(data);
                    }).catch(() => {
                        callback();
                    });
                },
            });
    
            this.fetchCities().then(data => {
                let tomSelectInstance = this.cityTarget.tomselect;
                tomSelectInstance.clearOptions();
                tomSelectInstance.addOptions(data);
            });
    
            this.cityTarget.addEventListener('search', (event) => {
                const searchTerm = event.detail.value;
    
                this.fetchCities(searchTerm).then(data => {
                    cityChoices.setChoices(data.map(item => ({
                        value: item.value,
                        label: item.label,
                        selected: false,
                        disabled: false
                    })), 'value', 'label', true);
                }).catch(error => {
                    console.error("Error fetching cities:", error);
                    cityChoices.setChoices([]);
                });
            });
        }
    
        window.addEventListener('savedJob', function(event) {
            let icon = document.querySelector(`#save${event.detail.job}`);
            if (icon) {
                icon.classList.remove('fa-bookmark-o');
                icon.classList.add('fa-bookmark', 'text-success');
                icon.querySelector('span').innerText = 'Saved';
                console.log(icon);
            }
        });
    
        window.addEventListener('unSavedJob', function(event) {
            let icon = document.querySelector(`#save${event.detail.job}`);
            if (icon) {
                icon.classList.remove('fa-bookmark', 'text-success');
                icon.classList.add('fa-bookmark-o');
                icon.querySelector('span').innerText = 'Save';
            }
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
            jobType: this.jobTypeTarget.value,
            postedDate: this.postedDateTarget.value,
            jobCategory: this.jobCategoryTarget.value,
            experience: this.experienceTarget.value,
            education: this.educationTarget.value,
            salary: this.salaryTarget.value,
            experience: this.experienceTarget.value,
            immediateHiring: this.immediateHiringTarget.checked
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
                this.jobsTarget.insertAdjacentHTML('beforeend', response.data.html);

            } else {
                // this.offsetValue = this.offsetValue + this.limitValue;
                this.jobsTarget.innerHTML = response.data.html;
            }

            this.loadMoreTarget.querySelector('span').innerHTML = response.data.remaining > 0 ? `Load More (${response.data.remaining})` : '';
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
            this.jobsTarget.innerHTML = loading_card;
        } else {
            this.jobsTarget.insertAdjacentHTML('beforeend', loading_card);
        }
    }

    removeLoaderEffect()
    {
        this.jobsTarget.querySelectorAll('.loader-item').forEach(job => job.remove());
    }

    saveJob(event)
    {
        event.preventDefault();
        
        axios.patch(event.params.url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if(response.data.saved)
                {
                    let icon = event.target.closest('div').querySelector('i');
                    
                    icon.classList.remove('fa-bookmark-o');
                    icon.classList.add('fa-bookmark', 'text-success');
                    // icon.querySelector('span').innerText = 'Saved';
                    console.log(icon);
                } else {
                    let icon = event.target.closest('div').querySelector('i');
                    
                    icon.classList.remove('fa-bookmark');
                    icon.classList.add('fa-bookmark-0', 'text-success');
                    // icon.querySelector('span').innerText = 'Saved';
                    console.log(icon);
                }
            })
            .catch();
    }
}