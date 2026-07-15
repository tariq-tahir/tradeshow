// app.js

/**
 * Manage global libraries like jQuery or THREE from the webpack.mix.js file
 */

// Import custom modules
import App from './modules/app.js';
import FrontendAdmin from './modules/frontend-admin.js';
import ProductSingle from './modules/product-single.js';
import AccountRegister from './modules/account-register.js';
import ProfilePublic from './modules/profile-public.js';
import ContactForm from './modules/contact-form.js';
//import ChangePassword from './modules/change-password.js';


new App();

// Run frontend admin logic if dashboard OR registration page exists
if (document.querySelector('.exporter-dashboard') || document.querySelector('.awps-register-form')) {
    new FrontendAdmin();
}

// Run product single page logic ONLY if product container exists
if (document.querySelector('.awps-product-container')) {
    new ProductSingle();
}

// Initialize contact form if present
if ( document.querySelector( '.awps-contact-form' ) ) {
    new ContactForm();
}

// Run registration form logic if registration form exists
if (document.querySelector('.awps-register-form')) {
    new AccountRegister();
}

// Run public profile logic if profile page exists
if (document.querySelector('.exporter-profile')) {
    new ProfilePublic();
}

// // Run change password logic if form exists
// if ( document.getElementById( 'change-password-form' ) ) {
//     new ChangePassword();
// }

// Run ContactForm logic if contact form exists
if (document.querySelector('.awps-contact-form')) {
    new ContactForm();
}


// Basic back-to-top functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.querySelector('.back-to-top');
    
    window.addEventListener('scroll', function() {
        backToTop.style.display = (window.pageYOffset > 300) ? 'flex' : 'none';
    });
    
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
