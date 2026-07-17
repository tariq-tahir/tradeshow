<?php
/**
 * Supplier Registration Form View
 */

// ✅ ROBUST RETRIEVAL: Check query_vars first, then fallback to raw $_GET
// $error = $_GET['awps_error'];
// if (empty($error) && isset($error)) {
//     $error = sanitize_text_field(wp_unslash($error));
// }

// $message = $_GET['awps_message'];
// if (empty($message) && isset($message)) {
//     $message = sanitize_text_field(wp_unslash($message));
// }

// ✅ Safe retrieval with null coalescing (prevents warnings)
$error = sanitize_text_field( wp_unslash( $_GET['awps_error'] ?? '' ) );
$message = sanitize_text_field( wp_unslash( $_GET['awps_message'] ?? '' ) );



// Preserve submitted values on error (for better UX)
$submitted = array(
    'company_name'    => isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '',
    'email'           => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
    'address'         => isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '',
    'state'           => isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '',
    'city'            => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
    'contact_person'  => isset( $_POST['contact_person'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_person'] ) ) : '',
    'designation'     => isset( $_POST['designation'] ) ? sanitize_text_field( wp_unslash( $_POST['designation'] ) ) : '',
    'whatsapp'        => isset( $_POST['whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp'] ) ) : '',
    'payment_plan'    => isset( $_POST['payment_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_plan'] ) ) : '70000',
);

// ✅ LOAD CITIES DATA + EXTRACT STATES DYNAMICALLY (NO HARDCODING)
$cities_by_state = function_exists( 'get_state_cities' ) ? get_state_cities() : array();
$states = array_keys( $cities_by_state ); // ← Extract state names from array keys

// ✅ Error & success message maps (translatable)
$error_messages = array(
    'missing_fields'    => __( 'Please fill in all required fields.', 'awps' ),
    'invalid_email'     => __( 'Please enter a valid email address.', 'awps' ),
    'email_exists'      => __( 'An account with this email already exists.', 'awps' ),
    'registration_failed' => __( 'Registration failed. Please try again.', 'awps' ),
    'invalid_whatsapp'  => __( 'Please enter a valid WhatsApp number (e.g., +923001234567).', 'awps' ),
    'payment_required'  => __( 'Please select a payment plan to continue.', 'awps' ),
    'terms_required'    => __( 'You must agree to the Terms & Conditions.', 'awps' ),
    'payment_cancelled' => __( 'Payment was cancelled. You can retry anytime.', 'awps' ),
);

$success_messages = array(
    'registration_success' => __( 'Registration successful! Please complete payment to activate your account.', 'awps' ),
    'payment_success'      => __( 'Payment successful! Your account is now active.', 'awps' ),
    'logged_out'           => __( 'You have been logged out successfully.', 'awps' ),
);
?>

<div class="awps-account-wrapper">
    <div class="awps-account-card awps-register-card">
        <h2><?php esc_html_e( 'Supplier Registration', 'awps' ); ?></h2>
        
        <!-- ✅ DISPLAY ERROR MESSAGES -->
        <?php if ( ! empty( $error ) ) : ?>
            <div class="awps-error" role="alert" aria-live="assertive">
                <?php
                // Map error codes to user-friendly messages
                $error_map = [
                    'missing_fields'    => __( 'Please fill in all required fields.', 'awps' ),
                    'invalid_email'     => __( 'Please enter a valid email address.', 'awps' ),
                    'email_exists'      => __( 'An account with this email already exists. Please use a different email or <a href="' . esc_url( home_url( '/my-account/?awps_action=login' ) ) . '">login</a>.', 'awps' ),
                    'registration_failed' => __( 'Registration failed. Please try again.', 'awps' ),
                    'invalid_whatsapp'  => __( 'Please enter a valid WhatsApp number (e.g., +923001234567).', 'awps' ),
                    'payment_required'  => __( 'Please select a payment plan to continue.', 'awps' ),
                    'terms_required'    => __( 'You must agree to the Terms & Conditions.', 'awps' ),
                    'payment_cancelled' => __( 'Payment was cancelled. You can retry anytime.', 'awps' ),
                    'payment_setup_failed' => __( 'Could not connect to payment gateway. Please try again or contact support.', 'awps' ),
                    'payment_verification_failed' => __( 'Payment verification failed. Please contact support.', 'awps' ),
                    'user_creation_failed' => __( 'Account creation failed. Please contact support.', 'awps' ),
                    'registration_expired' => __( 'Registration session expired. Please start over.', 'awps' ),
                    'missing_reg_data' => __( 'Registration data not found. Please start over.', 'awps' ),
                    'invalid_session' => __( 'Invalid payment session. Please try again.', 'awps' ),
                ];
                
                // Split multiple errors and display each
                $errors = array_filter( array_map( 'trim', explode( ',', $error ) ) );
                
                foreach ( $errors as $err_code ) :
                    if ( ! empty( $err_code ) ) :
                        $message = $error_map[ $err_code ] ?? __( 'An error occurred. Please try again.', 'awps' );
                        ?>
                        <p><?php echo wp_kses_post( $message ); ?></p>
                        <?php
                    endif;
                endforeach;
                ?>
            </div>

        
        <!-- ✅ DISPLAY SUCCESS MESSAGES -->
        <?php elseif ( $message ) : ?>
            <div class="awps-success" role="status" aria-live="polite">
                <?php echo esc_html( $success_messages[ $message ] ?? '' ); ?>
            </div>
        <?php endif; ?>
        
        <!-- ✅ REGISTRATION FORM -->
        <form method="post" 
            class="awps-register-form" 
            novalidate
            data-cities='<?php echo esc_attr(wp_json_encode($cities_by_state)); ?>'
            data-saved-city="<?php echo esc_attr($submitted['city'] ?? ''); ?>">
            
            <input type="hidden" name="awps_action" value="register">
            <?php wp_nonce_field( 'awps_register', 'awps_register_nonce' ); ?>
            
            <!-- Company Name -->
            <div class="form-group">
                <label for="company_name">
                    <?php esc_html_e( 'Company Name', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <input type="text" 
                       name="company_name" 
                       id="company_name" 
                       required 
                       value="<?php echo esc_attr( $submitted['company_name'] ); ?>"
                       placeholder="<?php esc_attr_e( 'e.g. ABC International', 'awps' ); ?>"
                       autocomplete="organization">
                <small class="form-hint">
                    <?php esc_html_e( 'This will generate your username (e.g., "abcinternational").', 'awps' ); ?>
                </small>
            </div>
            
            <!-- Business Email -->
            <div class="form-group">
                <label for="email">
                    <?php esc_html_e( 'Business Email', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       required 
                       value="<?php echo esc_attr( $submitted['email'] ); ?>"
                       placeholder="<?php esc_attr_e( 'you@company.com', 'awps' ); ?>"
                       autocomplete="email">
                <small class="form-hint">
                    <?php esc_html_e( 'This will be your login username.', 'awps' ); ?>
                </small>
            </div>
            
            <!-- Address -->
            <div class="form-group">
                <label for="address"><?php esc_html_e( 'Address', 'awps' ); ?></label>
                <input type="text" 
                       name="address" 
                       id="address" 
                       value="<?php echo esc_attr( $submitted['address'] ); ?>"
                       placeholder="<?php esc_attr_e( 'e.g. 123 Export Street, Karachi', 'awps' ); ?>"
                       class="regular-text"
                       autocomplete="street-address">
            </div>
            
            <!-- State & City (Dynamic - uses existing initStateCity function) -->
            <div class="form-row form-row-two-col">
                <div class="form-group">
                    <label for="state">
                        <?php esc_html_e( 'State', 'awps' ); ?> 
                        <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                    </label>
                    <select name="state" id="state" class="regular-text" required autocomplete="address-level1">
                        <option value=""><?php esc_html_e( '— Select State —', 'awps' ); ?></option>
                        <?php foreach ( $states as $state ) : ?>
                            <option value="<?php echo esc_attr( $state ); ?>" <?php selected( $submitted['state'], $state ); ?>>
                                <?php echo esc_html( $state ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="city">
                        <?php esc_html_e( 'City', 'awps' ); ?> 
                        <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                    </label>
                    <select name="city" id="city" class="regular-text" required <?php disabled( empty( $submitted['state'] ) ); ?> autocomplete="address-level2">
                        <option value="">
                            <?php echo empty( $submitted['state'] ) ? esc_html__( '— Select State First —', 'awps' ) : esc_html__( '— Select City —', 'awps' ); ?>
                        </option>
                        <?php 
                        // Pre-load cities if state was submitted (for form repopulation after error)
                        if ( ! empty( $submitted['state'] ) && ! empty( $cities_by_state[ $submitted['state'] ] ) ) :
                            foreach ( $cities_by_state[ $submitted['state'] ] as $city ) : 
                        ?>
                            <option value="<?php echo esc_attr( $city ); ?>" <?php selected( $submitted['city'], $city ); ?>>
                                <?php echo esc_html( $city ); ?>
                            </option>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </select>
                </div>
            </div>
            
            <!-- Contact Person Details -->
            <div class="form-row form-row-two-col">
                <div class="form-group">
                    <label for="contact_person">
                        <?php esc_html_e( 'Contact Person', 'awps' ); ?> 
                        <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                    </label>
                    <input type="text" 
                           name="contact_person" 
                           id="contact_person" 
                           required 
                           value="<?php echo esc_attr( $submitted['contact_person'] ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. John Smith', 'awps' ); ?>"
                           autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="designation">
                        <?php esc_html_e( 'Designation', 'awps' ); ?> 
                        <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                    </label>
                    <input type="text" 
                           name="designation" 
                           id="designation" 
                           required 
                           value="<?php echo esc_attr( $submitted['designation'] ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. Export Manager', 'awps' ); ?>"
                           autocomplete="organization-title">
                </div>
            </div>
            
            <!-- WhatsApp -->
            <div class="form-group">
                <label for="whatsapp">
                    <?php esc_html_e( 'WhatsApp Number', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <input type="tel" 
                       name="whatsapp" 
                       id="whatsapp" 
                       required 
                       value="<?php echo esc_attr( $submitted['whatsapp'] ); ?>"
                       placeholder="<?php esc_attr_e( '+923001234567', 'awps' ); ?>"
                       pattern="^\+?[0-9\s\-\(\)]{7,15}$"
                       autocomplete="tel">
                <small class="form-hint">
                    <?php esc_html_e( 'Include country code (e.g., +92 for Pakistan).', 'awps' ); ?>
                </small>
            </div>
            
            
            
            
            <!-- Payment Plan Selection -->
            <div class="form-group">
                <label>
                    <?php esc_html_e( 'Select Payment Plan', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <div class="payment-plans-grid">
                    
                    <!-- Basic Plan -->
                    <label class="payment-plan-card <?php echo $submitted['payment_plan'] === '70000' ? 'selected' : ''; ?>">
                        <input type="radio" name="payment_plan" value="70000" <?php checked( $submitted['payment_plan'], '70000' ); ?> required>
                        <span class="plan-price">PKR 70,000</span>
                        <span class="plan-name"><?php esc_html_e( 'Basic Plan', 'awps' ); ?></span>
                    </label>
                    
                    <!-- Premium Plan -->
                    <label class="payment-plan-card <?php echo $submitted['payment_plan'] === '150000' ? 'selected' : ''; ?>">
                        <input type="radio" name="payment_plan" value="150000" <?php checked( $submitted['payment_plan'], '150000' ); ?> required>
                        <span class="plan-price">PKR 150,000</span>
                        <span class="plan-name"><?php esc_html_e( 'Premium Plan', 'awps' ); ?></span>
                    </label>
                    
                </div>
            </div>
            
            <!-- Terms & Conditions -->
            <div class="form-group form-group-checkbox">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms_accepted" required>
                    <span>
                        <?php esc_html_e( 'I agree to the', 'awps' ); ?> 
                        <a href="<?php echo esc_url( home_url( '/terms-conditions/' ) ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Terms & Conditions', 'awps' ); ?>
                        </a> 
                        <?php esc_html_e( 'and', 'awps' ); ?> 
                        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Privacy Policy', 'awps' ); ?>
                        </a>.
                    </span>
                </label>
            </div>
            
            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="button button-primary button-full">
                    <?php esc_html_e( 'Register Account', 'awps' ); ?>
                </button>
            </div>
            
            <!-- Login Link -->
            <p class="form-links">
                <a href="<?php echo esc_url( home_url( '/my-account/?awps_action=login' ) ); ?>">
                    <?php esc_html_e( 'Already have an account? Login', 'awps' ); ?>
                </a>
            </p>
        </form>
    </div>
</div>