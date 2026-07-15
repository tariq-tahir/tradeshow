<?php
/**
 * Supplier Login Form View
 * 
 * @package awps
 */

// ✅ Get error/message with MULTIPLE fallback methods (robust parsing)
// NOTE: We use 'awps_error' and 'awps_message' to match our redirect URLs
$error = '';
$message = '';

// Method 1: get_query_var (registered vars) - check awps_error first
$error_qv = get_query_var( 'awps_error' );
if ( ! empty( $error_qv ) ) {
    $error = sanitize_text_field( $error_qv );
}

// Method 2: $_GET direct
if ( empty( $error ) && isset( $_GET['awps_error'] ) ) {
    $error = sanitize_text_field( wp_unslash( $_GET['awps_error'] ) );
}

// Method 3: Parse REQUEST_URI manually (most reliable)
if ( empty( $error ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
    parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ) ?? '', $parsed );
    if ( isset( $parsed['awps_error'] ) ) {
        $error = sanitize_text_field( $parsed['awps_error'] );
    }
}

// Same for message (awps_message)
$message_qv = get_query_var( 'awps_message' );
if ( ! empty( $message_qv ) ) {
    $message = sanitize_text_field( $message_qv );
} elseif ( isset( $_GET['awps_message'] ) ) {
    $message = sanitize_text_field( wp_unslash( $_GET['awps_message'] ) );
} elseif ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
    parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ) ?? '', $parsed );
    if ( isset( $parsed['awps_message'] ) ) {
        $message = sanitize_text_field( $parsed['awps_message'] );
    }
}

// ✅ Error & success message maps (translatable)
$error_messages = array(
    'missing_fields'      => __( 'Please fill in all fields.', 'awps' ),
    'invalid_credentials' => __( 'Invalid email or password. Please try again.', 'awps' ),
    'invalid_role'        => __( 'This account does not have supplier access.', 'awps' ),
    'login_required'      => __( 'Please login to access the dashboard.', 'awps' ),
    'disabled'            => __( 'Your account has been disabled. Please contact support.', 'awps' ),
);

$success_messages = array(
    'registration_success' => __( 'Registration successful! Please login with your credentials.', 'awps' ),
    'logged_out'           => __( 'You have been successfully logged out.', 'awps' ),
    'reset_sent'           => __( 'Password reset link has been sent to your email.', 'awps' ),
);
?>

<div class="awps-account-wrapper">
    <div class="awps-account-card awps-login-card">
        <h2><?php esc_html_e( 'Supplier Login', 'awps' ); ?></h2>
        
        <!-- ✅ DISPLAY ERROR MESSAGES -->
        <?php if ( ! empty( $error ) ) : ?>
            <div class="awps-error" role="alert" aria-live="assertive">
                <?php echo esc_html( $error_messages[ $error ] ?? __( 'An error occurred. Please try again.', 'awps' ) ); ?>
            </div>
        <?php endif; ?>
        
        <!-- ✅ DISPLAY SUCCESS MESSAGES -->
        <?php if ( ! empty( $message ) ) : ?>
            <div class="awps-success" role="status" aria-live="polite">
                <?php echo esc_html( $success_messages[ $message ] ?? '' ); ?>
            </div>
        <?php endif; ?>
        
        <!-- ✅ LOGIN FORM -->
        <form method="post" class="awps-login-form" id="awps-login-form" novalidate>
            <input type="hidden" name="awps_action" value="login">
            <?php wp_nonce_field( 'awps_login', 'awps_login_nonce' ); ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">
            
            <!-- Email -->
            <div class="form-group">
                <label for="login-email">
                    <?php esc_html_e( 'Email Address', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       id="login-email" 
                       required 
                       value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>"
                       autocomplete="email"
                       placeholder="<?php esc_attr_e( 'you@company.com', 'awps' ); ?>">
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="login-password">
                    <?php esc_html_e( 'Password', 'awps' ); ?> 
                    <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                </label>
                <input type="password" 
                       name="password" 
                       id="login-password" 
                       required 
                       autocomplete="current-password"
                       placeholder="<?php esc_attr_e( '••••••••', 'awps' ); ?>">
            </div>
            
            <!-- Submit -->
            <div class="form-actions">
                <button type="submit" class="button button-primary button-full">
                    <?php esc_html_e( 'Login', 'awps' ); ?>
                </button>
            </div>
            
            <!-- Links -->
            <p class="form-links">
                <a href="<?php echo esc_url( home_url( '/my-account/?awps_action=register' ) ); ?>">
                    <?php esc_html_e( 'Register as Supplier', 'awps' ); ?>
                </a>
                <span class="separator" aria-hidden="true">|</span>
                <a href="<?php echo esc_url( home_url( '/my-account/?awps_action=reset' ) ); ?>">
                    <?php esc_html_e( 'Forgot Password?', 'awps' ); ?>
                </a>
            </p>
        </form>
    </div>
</div>