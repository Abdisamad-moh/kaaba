import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets   
 = ['element'];

  connect() {
    // this.elementTarget.classList.add('my-controller-connected');
    // alert("hi from second controller")
  }

  submit(event){
    event.preventDefault();
  }
}