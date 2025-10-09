import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';

export default class extends Controller {
    static targets = [];
    static values = {
        existingSkills: Array,
        skills: Array,
        careerSkills: Array,
        percentage: Number,
        details: Array,
        careerUrl: String,
        skillUrl: String,
        requiredSkills: Array,
        preferredSkills: Array,
        careerSkills: Array,
    }
    educationModal;

    connect() {
        let career_input = this.element.querySelector('#job_form_title');
        const careerUrl = this.careerUrlValue
        const skillUrl = this.skillUrlValue;
        
        if(!career_input.tomselect)
        {
            const  element = new TomSelect(career_input, {
                create: true,
                maxItems: 1,
                delimiter: '\n',
                load: (query, callback) => {
                    fetch(`${careerUrl}?search=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            callback(data);
                        }).catch(() => {
                            callback();
                        });
                },
            });
        }

        const skill_inputs = this.element.querySelectorAll('.skill_input');

        career_input.addEventListener('change', function(event) {
            
            fetch(`${skillUrl}?search=${encodeURIComponent('')}&prevOptions=${encodeURIComponent('')}&career=${encodeURIComponent(event.target.value)}`)
                        .then(response => response.json())
                        .then(data => {
                            Array.from(skill_inputs).forEach((element) => {
                                let tomSelectInstance = element.tomselect;
                                tomSelectInstance.clearOptions();
                                tomSelectInstance.addOptions(data);
                            })
                            
                        }).catch((err) => {
                            console.log(err)
                        });
        })

        Array.from(skill_inputs).forEach((element) => {
            const career = document.querySelector('#job_form_title');
            const selected_skills = element.id == 'job_form_required_skill' ? this.requiredSkillsValue : this.preferredSkillsValue;
            console.log(selected_skills);
            new TomSelect(element, {
                create: true, // Allows users to add new options
                maxItems: null, // Limit selection to one item
                delimiter: '|',
                items: selected_skills.map(skill => skill.value),
                options: selected_skills,
                load: (query, callback) => {
                    fetch(`${skillUrl}?search=${encodeURIComponent(query)}&prevOptions=${encodeURIComponent('')}&career=${encodeURIComponent(career.value)}`)
                        .then(response => response.json())
                        .then(data => {
                            callback(data);
                        }).catch(() => {
                            callback();
                        });
                },
                // Other custom settings...
            });

            let $tomInstance = element.tomselect;
            $tomInstance.addOptions(this.careerSkillsValue);
        });
        // this.modalInstance = Modal.getOrCreateInstance(this.modalTarget);
        // const selectElement = document.querySelector('#job_seeker_resume_skills');
        // const career = document.querySelector('#job_seeker_resume_jobTitle');
        // console.log('something here')
        // const profileCompletionModal = new Modal(document.getElementById('profileCompletionModal'));
        // // console.log(this.percentageValue, this.showAlert);
        // if(this.percentageValue) {
        //     let html = ``;
        //     this.detailsValue.forEach(function(error) {
        //         html += `<li>${error}</li>`;
        //     });
        //     document.getElementById('profileCompletionModal').querySelector('.profile_list').innerHTML = html;
        //     profileCompletionModal.show()
        // }
        
        // if (selectElement) {
        //     new TomSelect(selectElement, {
        //         create: true, // Allows users to add new options
        //         maxItems: null, // Limit selection to one item
        //         items: this.existingSkillsValue.map(skill => skill.value),
        //         options: this.existingSkillsValue,
        //         load: (query, callback) => {
        //             fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent(query)}&prevOptions=${encodeURIComponent('')}&career=${encodeURIComponent(career.value)}`)
        //                 .then(response => response.json())
        //                 .then(data => {
        //                     callback(data);
        //                 }).catch(() => {
        //                     callback();
        //                 });
        //         },
        //         // Other custom settings...
        //     });

        //     let $tomInstance = selectElement.tomselect;
        //     $tomInstance.addOptions(this.careerSkillsValue);
        // }

        // career.addEventListener('change', function(event) {
        //     const prevOptions = selectElement.value;

        //     fetch(`/jobseeker/fetch_skills?search=${encodeURIComponent('')}&prevOptions=${encodeURIComponent(prevOptions)}&career=${encodeURIComponent(career.value)}`)
        //                 .then(response => response.json())
        //                 .then(data => {
        //                     let tomSelectInstance = selectElement.tomselect;
        //                     tomSelectInstance.clearOptions();
        //                     tomSelectInstance.addOptions(data);
        //                 }).catch((err) => {
        //                     console.log(err)
        //                 });
        // })

        
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