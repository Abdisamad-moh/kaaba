import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';
import axios from "axios";
import { Modal } from 'bootstrap';


export default class extends Controller {
    static targets = ["modal", "modalBody", "form"];
    

    connect()
    {
        this.modal = new Modal(this.modalTarget);

        
    }
}