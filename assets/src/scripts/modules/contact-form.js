/**
 * Contact Form Functionality
 * Handles submission timestamp, basic validation feedback, and UX enhancements
 */

export default class ContactForm {
    constructor() {
        this.form = document.getElementById( 'awps-contact-form' );
        if ( ! this.form ) return;
        
        this.submitBtn = document.getElementById( 'contact-submit-btn' );
        this.loadTimestamp = Date.now();
        
        this.init();
    }

    init() {
        this.form.addEventListener( 'submit', ( e ) => this.handleSubmit( e ) );
    }

    handleSubmit( e ) {
        // Set elapsed time for rate-limiting backend check
        const elapsedSeconds = Math.floor( ( Date.now() - this.loadTimestamp ) / 1000 );
        const timeInput = document.getElementById( 'submit_time' );
        if ( timeInput ) {
            timeInput.value = elapsedSeconds;
        }

        // Basic client-side validation feedback (optional enhancement)
        const requiredFields = this.form.querySelectorAll( '[required]' );
        let isValid = true;

        requiredFields.forEach( field => {
            if ( ! field.value.trim() ) {
                isValid = false;
                field.classList.add( 'error' );
                
                // Remove error state on input
                field.addEventListener( 'input', function handler() {
                    field.classList.remove( 'error' );
                    field.removeEventListener( 'input', handler );
                }, { once: true } );
            }
        } );

        if ( ! isValid ) {
            e.preventDefault();
            this.showValidationMessage( __( 'Please fill in all required fields.', 'awps' ) );
            return;
        }

        // Show loading state
        this.setLoadingState( true );
    }

    setLoadingState( loading ) {
        if ( ! this.submitBtn ) return;
        
        if ( loading ) {
            this.submitBtn.disabled = true;
            this.submitBtn.dataset.originalText = this.submitBtn.innerHTML;
            this.submitBtn.innerHTML = '<span class="spinner"></span> ' + __( 'Sending...', 'awps' );
        } else {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = this.submitBtn.dataset.originalText || __( 'Send Message', 'awps' );
        }
    }

    showValidationMessage( message ) {
        // Remove existing messages
        const existing = this.form.querySelector( '.validation-message' );
        if ( existing ) existing.remove();

        // Create and insert message
        const msg = document.createElement( 'div' );
        msg.className = 'validation-message error';
        msg.setAttribute( 'role', 'alert' );
        msg.textContent = message;
        
        this.form.insertBefore( msg, this.form.firstChild );
        
        // Auto-remove after 5 seconds
        setTimeout( () => msg.remove(), 5000 );
    }
}