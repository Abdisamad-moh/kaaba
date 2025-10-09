import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';

export default class extends Controller {
    static targets = ['message', 'phones', 'modal', 'modalForm', 'modalTitle'];
    static values = {
        existingSkills: Array,
        skills: Array,
        careerSkills: Array,
        percentage: Number,
        details: Array,
    }
    educationModal;

    connect() {
        
        this.modalInstance = Modal.getOrCreateInstance(this.modalTarget);
        const selectElement = document.querySelector('#job_seeker_resume_skills');
        const career = document.querySelector('#job_seeker_resume_jobTitle');
        
        const profileCompletionModal = new Modal(document.getElementById('profileCompletionModal'));
        // console.log(this.percentageValue, this.showAlert);
        if(this.percentageValue) {
            let html = ``;
            this.detailsValue.forEach(function(error) {
                html += `<li style="list-style: disc; font-size: 1.3em;font-size: 1.3em;">${error}</li>`;
            });
            document.getElementById('profileCompletionModal').querySelector('.profile_list').innerHTML = html;
            profileCompletionModal.show()
        }
        
        if (selectElement) {
            new TomSelect(selectElement, {
                create: true, // Allows users to add new options
                maxItems: null, // Limit selection to one item
                items: this.existingSkillsValue.map(skill => skill.value),
                options: this.existingSkillsValue,
                load: (query, callback) => {
                    fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent(query)}&prevOptions=${encodeURIComponent('')}&career=${encodeURIComponent(career.value)}`)
                        .then(response => response.json())
                        .then(data => {
                            callback(data);
                        }).catch(() => {
                            callback();
                        });
                },
                // Other custom settings...
            });

            let $tomInstance = selectElement.tomselect;
            $tomInstance.addOptions(this.careerSkillsValue);
        }

        career.addEventListener('change', function(event) {
            const prevOptions = selectElement.value;

            fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent('')}&prevOptions=${encodeURIComponent(prevOptions)}&career=${encodeURIComponent(career.value)}`)
                        .then(response => response.json())
                        .then(data => {
                            let tomSelectInstance = selectElement.tomselect;
                            tomSelectInstance.clearOptions();
                            tomSelectInstance.addOptions(data);
                        }).catch((err) => {
                            console.log(err)
                        });
        })

        document.addEventListener('resume:updated', () => {
            this.messageTarget.innerHTML = 
            `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> Resume updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            console.log('yeey');
        });
        
    }

    fetchOptions(careerId, query, callback)
    {
        console.log(careerId);
        fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent(query)}&careerId=${careerId}`)
            .then(response => response.json())
            .then(data => {
                callback(data);
            }).catch(() => {
                callback();
            });
    }

    loadForm({params: {url, title, action, form_type}}) {
        
        this.modalTitleTarget.innerHTML = title;
        
        axios.get(url)
            .then((response) => {
                
                this.modalFormTarget.innerHTML = response.data;
                this.modalFormTarget.querySelector('form').action = url;
                this.addFormSubmitListener(this.modalFormTarget.querySelector('form'), form_type);
                this.modalInstance.show();
                
                if(form_type === 'experience_form') CKEDITOR.replace(this.modalFormTarget.querySelector('textarea').id, this.getCkeditorConfig());
                
               
            })
            .catch(error => console.error('sorry, something went wrong', error));
    }

    getCkeditorConfig() {
        return {
            // Specify the language (adjust to your requirements)
            "toolbar": [
                ["Bold", "Italic"],  // Basic formatting
                ["NumberedList", "BulletedList", "-", "Outdent", "Indent"],  // List and indentation controls
                ["Link", "Unlink"],  // Link management
                ["About"]  // CKEditor information
            ],
            "uiColor": "#ffffff",  // Sets the UI color for the editor (white background)
            "language": "en"  // Sets the language for the editor interface
        };
    }

    // educationForm({ params: { url, action }})
    // {
    //     if(action == 'update')
    //         this.modalTitleTarget.innerHTML = 'Update Education';
    //     else 
    //         this.modalTitleTarget.innerHTML = 'Add Education';

    //     axios.get(url)
    //         .then((response) => {
    //             this.modalFormTarget.innerHTML = response.data;
    //             this.modalFormTarget.querySelector('form').action = url;
    //             this.modalInstance.show();
    //         })
    //         .catch(error => console.error('sorry, something went wrong', error));
    // }
    educationForm(event) {
        // console.log(event.params);return;
        let params = event.params;
        params.form_type = 'education_form';
        params.title = params.action === 'update' ? 'Update Education' : 'Add Education';
        // console.log(params);
        this.loadForm({ params });
    }
    workExperienceForm(event) {
        let params = event.params;
        params.form_type = 'experience_form';
        params.title = params.action === 'update' ? 'Update Work Experience' : 'Add Work Experience';
        this.loadForm({ params });
    }

    certificationForm(event) {
        let params = event.params;
        params.form_type = 'certification_form';
        params.title = params.action === 'update' ? 'Update Certification' : 'Add Certification';
        this.loadForm({ params });
    }

    // workExperienceForm({ params: { url, action }})
    // {
    //     if(action == 'update')
    //         this.modalTitleTarget.innerHTML = 'Update Work Experience';
    //     else 
    //         this.modalTitleTarget.innerHTML = 'Add New Experience Record';

    //     axios.get(url)
    //         .then((response) => {
    //             this.modalFormTarget.innerHTML = response.data;
    //             this.modalFormTarget.querySelector('form').action = url;
    //             this.modalInstance.show();
    //         })
    //         .catch(error => console.error('sorry, something went wrong'));

    // }

    // certificationForm({params: { url, action }})
    // {
    //     if(action == 'update')
    //         this.modalTitleTarget.innerHTML = 'Update Certification';
    //     else 
    //         this.modalTitleTarget.innerHTML = 'Add Certification';

    //     axios.get(url)
    //         .then((response) => {
    //             this.modalFormTarget.innerHTML = response.data;
    //             this.modalFormTarget.querySelector('form').action = url;
    //             this.modalInstance.show();
    //         })
    //         .catch(error => console.error('sorry, something went wrong'));
    // }

    addFormSubmitListener(form, form_type = null) {
        form.addEventListener('submit', (event) => {
            if(form_type === 'experience_form') CKEDITOR.instances['job_seeker_experience_duties'].updateElement(); // Update the textarea

            event.preventDefault();
            const formData = new FormData(form);
            const url = form.action;
            axios.post(url, formData)
                .then(response => {
                    this.handleFormSuccess(response, form_type);
                })
                .catch(error => {
                    this.handleFormError(error, form_type);
                });
        });
    }

    handleFormSuccess(response, form_type) {
        this.modalInstance.hide();
        window.location.hash = '#' + form_type;
        window.location.reload();
    }

    handleFormError(error, form_type = null) {
        console.error('Form submission error:', error);

        if (error.response && error.response.status === 422) {
            this.modalFormTarget.innerHTML = error.response.data;
            this.addFormSubmitListener(this.modalFormTarget.querySelector('form'), form_type);
            this.scrollToTop();
        } else {
            // this.showMessage('An unexpected error occurred.', 'danger');
            console.log('Sorry, something went wrong.');
        }
    }

    scrollToTop() {
        this.modalTarget.scrollTo({
            top: 0,
            behavior: 'smooth'
        });  // Set the scroll position of the modal body to the top
    }

    // 
    toggleCertificateExpirable(event)
    {
        let expirable = event.target;
        let expiration_date_input = expirable.closest('form').querySelector('#job_seeker_certificate_expiresAt');

        expiration_date_input.value = null;
        expiration_date_input.closest('.row').classList.toggle('d-none');
    }

    toggleExperienceIsCurrent(event)
    {
        let current = event.target;
        
        let finish_date = current.closest('form').querySelector('#job_seeker_experience_finishDate');

        finish_date.value = null;
        finish_date.closest('.row').classList.toggle('d-none');
    }

    goToList(event)
    {
        window.location.replace(event.params.url);
    }
    
}