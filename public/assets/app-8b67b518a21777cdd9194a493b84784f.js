import './bootstrap.js';
import { Modal } from 'bootstrap';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import '../bundles/fosckeditor/ckeditor.js'; 

if(window.location.pathname == '/jobseeker/profile') {
    CKEDITOR.basePath = '/bundles/fosckeditor/';
}

import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');




