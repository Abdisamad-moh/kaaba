import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';
import { Modal } from 'bootstrap';
import axios from 'axios';
import TomSelect from 'tom-select';
export default class extends Controller {
    static targets = [];
    static values = {
        jobUrl: String,
    }

    connect()
    {
        
    }
    copyUrl(event)
    {
        const url = event.params.url;

        // const toastLiveExample = document.getElementById('liveToast')
        // const toastBootstrap = Toast.getOrCreateInstance(toastLiveExample)

        navigator.clipboard.writeText(url)
            .then(() => {
                // toastBootstrap.show();
                // if(toastBootstrap.isShown)
                // {
                //     setTimeout(() => {
                //         toastBootstrap.dispose()
                //     }, 3000)
                // }

                alert('The link is copied to the clipboard.')
            })
            .catch((err) => {
                console.log(err);
            })
        ;

    }

}