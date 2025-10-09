import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';
export default class extends Controller {
    static targets = ['tenderDetailCountry', 'reportDiv', 'profileCompletionModal'];
    static values = {
        percentage: Number,
        details: Array,
        showAlert: Boolean,
        url: String,
        title: String,
        jobid: String,
    }

    connect()
    {
        //
        // this.applyFilter();
        const profileCompletionModal = new Modal(document.getElementById('profileCompletionModal'));
        // console.log(this.percentageValue, this.showAlert);
        if(this.percentageValue) {
            let html = ``;
            this.detailsValue.forEach(function(error) {
                html += `<li style="list-style: disc; font-size: 1.3em;">${error}</li>`;
            });
            document.getElementById('profileCompletionModal').querySelector('.profile_list').innerHTML = html;
            profileCompletionModal.show()
        }
        
    }
    copyUrl(event)
    {
        const url = window.location.href;
        const toastLiveExample = document.getElementById('liveToast')
        const toastBootstrap = Toast.getOrCreateInstance(toastLiveExample)

        navigator.clipboard.writeText(url)
            .then(() => {
                toastBootstrap.show();
                if(toastBootstrap.isShown)
                {
                    setTimeout(() => {
                        toastBootstrap.dispose()
                    }, 3000)
                }
            })
            .catch((err) => {
                console.log(err);
            })
        ;

    }

    showReportForm()
    {
        this.reportDivTarget.classList.remove('d-none');
        this.reportDivTarget.querySelector('#description').focus();
    }

    submitReportForm({params: {user}})
    {
        if(!user)
        {
            alert('Please login first to be able to report this job');
            return;
        }

        this.reportDivTarget.querySelector('form').submit();
    }

    applyFilter(loadMore = false) {
        if(loadMore !== true) {
            this.offsetValue = 0;
            this.loaderEffect(true);
        }
        
        const params = new URLSearchParams({
            offset: this.offsetValue ?? 0,
            limit: this.limitValue ?? 6,
            title: this.titleValue,
            excludeJobId: this.jobidValue,
            similarJobs: 1,
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

            // this.loadMoreTarget.querySelector('span').innerText = response.data.remaining > 0 ? `Load More (${response.data.remaining})` : 'No More Jobs Found';
        })
        .catch(error => {
            console.error('Sorry, something went wrong', error);
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

}