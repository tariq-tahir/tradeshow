<?php
/**
 * Password Reset Request View
 * 
 * @package awps
 */
?>
<div class="awps-account-wrapper">
    <div class="awps-account-card">
        <h2><?php esc_html_e( 'Reset Password', 'awps' ); ?></h2>
        
        <?php if ( 'reset_sent' === $message ) : ?>
            <div class="awps-success" role="status" aria-live="polite">
                <?php esc_html_e( 'If an account exists with this email, you will receive a password reset link.', 'awps' ); ?>
            </div>
            <p>
                <a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">
                    <?php esc_html_e( '← Back to Login', 'awps' ); ?>
                </a>
            </p>
            
        <?php else : ?>
            
            <?php if ( $error ) : ?>
                <div class="awps-error" role="alert" aria-live="assertive">
                    <?php
                    $error_message = 'invalid_email' === $error
                        ? __( 'Please enter a valid email address.', 'awps' )
                        : __( 'An error occurred. Please try again.', 'awps' );
                    echo esc_html( $error_message );
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="awps-reset-form" 
                action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" 
                novalidate>
                
                <!-- ✅ CRITICAL: This tells WordPress which handler to use -->
                <input type="hidden" name="action" value="awps_reset_password">
                
                <!-- Keep existing nonce and fields -->
                <?php wp_nonce_field( 'awps_reset', 'awps_reset_nonce' ); ?>
                
                <p>
                    <label for="reset-email">
                        <?php esc_html_e( 'Email Address', 'awps' ); ?> 
                        <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="reset-email" 
                        required 
                        autocomplete="email"
                        value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>"
                    >
                </p>
                
                <p class="awps-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Send Reset Link', 'awps' ); ?>
                    </button>
                </p>
                
                <p class="awps-form-links">
                    <a href="<?php echo esc_url( home_url( '/my-account/?awps_action=login' ) ); ?>">
                        <?php esc_html_e( '← Back to Login', 'awps' ); ?>
                    </a>
                </p>
            </form>
            
        <?php endif; ?>
    </div>
</div>