<?php
/**
 * Contact Form Template
 * 
 * @package awps
 */

// Check for success/error messages (sanitized)
$form_success = isset( $_GET['form_success'] ) && '1' === $_GET['form_success'];
$form_error   = isset( $_GET['form_error'] ) ? sanitize_text_field( wp_unslash( $_GET['form_error'] ) ) : '';

// Error message mapping (translatable)
$error_messages = array(
    'required'   => __( 'All required fields must be filled.', 'awps' ),
    'email'      => __( 'Please enter a valid email address.', 'awps' ),
    'rate_limit' => __( 'Please wait 60 seconds before submitting again.', 'awps' ),
    'default'    => __( 'Something went wrong. Please try again.', 'awps' ),
);
?>

<?php if ( $form_success ) : ?>
    <div class="contact-form-message success" role="status" aria-live="polite">
        <?php esc_html_e( '✅ Thank you! We\'ll get back to you soon.', 'awps' ); ?>
    </div>
<?php elseif ( $form_error ) : ?>
    <div class="contact-form-message error" role="alert" aria-live="assertive">
        <?php 
        $message = $error_messages[ $form_error ] ?? $error_messages['default'];
        echo esc_html( $message ); 
        ?>
    </div>
<?php endif; ?>

<div class="awps-contact-form">
    <form method="post" class="contact-form" id="awps-contact-form" novalidate>
        <?php wp_nonce_field( 'awps_contact_form', 'awps_contact_form_nonce' ); ?>

        <!-- 🕷️ Honeypot field (hidden from humans, catches bots) -->
        <div class="honeypot-field" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
            <label>
                <?php esc_html_e( 'Leave this field empty', 'awps' ); ?>: 
                <input type="text" name="website_url" autocomplete="off" tabindex="-1" value="">
            </label>
        </div>

        <!-- ⏱️ Submission time tracker -->
        <input type="hidden" name="submit_time" id="submit_time" value="0">

        <div class="form-group">
            <label for="contact-name"><?php esc_html_e( 'Full Name', 'awps' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span></label>
            <input type="text" 
                   id="contact-name" 
                   name="name" 
                   value="<?php echo isset( $_POST['name'] ) ? esc_attr( wp_unslash( $_POST['name'] ) ) : ''; ?>" 
                   required
                   autocomplete="name">
        </div>

        <div class="form-group">
            <label for="contact-email"><?php esc_html_e( 'Email Address', 'awps' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span></label>
            <input type="email" 
                   id="contact-email" 
                   name="email" 
                   value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" 
                   required
                   autocomplete="email">
        </div>

        <div class="form-group">
            <label for="contact-subject"><?php esc_html_e( 'Subject', 'awps' ); ?></label>
            <input type="text" 
                   id="contact-subject" 
                   name="subject" 
                   value="<?php echo isset( $_POST['subject'] ) ? esc_attr( wp_unslash( $_POST['subject'] ) ) : ''; ?>"
                   autocomplete="off">
        </div>

        <div class="form-group">
            <label for="contact-message"><?php esc_html_e( 'Your Message', 'awps' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span></label>
            <textarea id="contact-message" 
                      name="message" 
                      rows="5" 
                      required
                      autocomplete="off"><?php echo isset( $_POST['message'] ) ? esc_textarea( wp_unslash( $_POST['message'] ) ) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary" id="contact-submit-btn">
                <?php esc_html_e( 'Send Message', 'awps' ); ?>
            </button>
        </div>
    </form>
</div>