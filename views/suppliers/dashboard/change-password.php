<?php
// views/exporters/dashboard/change-password.php

// Security: prevent direct access
defined('ABSPATH') or exit;

// Assume $user is passed in from index.php
if (!isset($user) || !is_a($user, 'WP_User')) {
    wp_die('Invalid access.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['awps_change_password_nonce'])) {
    if (wp_verify_nonce($_POST['awps_change_password_nonce'], 'awps_change_password')) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';

        $errors = [];

        if (!wp_check_password($current_pass, $user->user_pass, $user->ID)) {
            $errors[] = 'Current password is incorrect.';
        }

        if (empty($new_pass)) {
            $errors[] = 'New password cannot be empty.';
        }

        if (strlen($new_pass) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (empty($errors)) {
            wp_set_password($new_pass, $user->ID);
            wp_logout();
            // Redirect to /my-account/ with success message
            $my_account_url = add_query_arg('password_changed', '1', site_url('/my-account/'));
            wp_safe_redirect($my_account_url);
            exit;
        } else {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        }
    } else {
        echo '<div class="notice notice-error"><p>❌ Security check failed.</p></div>';
    }
}
?>

<div class="awps-frontend-form">
    <h3>🔐 Change Password</h3>
    <form method="post" id="change-password-form">
        <?php wp_nonce_field('awps_change_password', 'awps_change_password_nonce'); ?>

        <p>
            <label for="current_password">Current Password *</label>
            <input type="password" id="current_password" name="current_password" required style="width: 100%; max-width: 300px;">
        </p>

        <p>
            <label for="new_password">New Password *</label>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <div style="position: relative; flex: 1; min-width: 200px; max-width: 300px;">
                    <input type="password" id="new_password" name="new_password" required style="width: 100%; padding-right: 40px;">
                    <button type="button" id="toggle-new-password" 
                        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 14px; color: #666;"
                        aria-label="Show password">
                        👁️
                    </button>
                </div>
                <button type="button" id="generate-password" class="button" style="white-space: nowrap;">Generate Password</button>
            </div>
            <div id="password-strength" style="margin-top: 6px; height: 4px; background: #eee; border-radius: 2px; overflow: hidden; max-width: 300px;">
                <div id="strength-bar" style="height: 100%; width: 0%; transition: width 0.3s, background 0.3s;"></div>
            </div>
            <small id="strength-text" style="display: block; margin-top: 4px; color: #555;">Enter a password</small>
        </p>

        <p>
            <label for="confirm_password">Confirm New Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; max-width: 300px;">
            <small id="confirm-message" style="display: block; margin-top: 4px; color: #555;"></small>
        </p>

        <p>
            <button type="submit" class="button button-primary" id="submit-btn" disabled>Update Password</button>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const confirmMessage = document.getElementById('confirm_message');
    const submitBtn = document.getElementById('submit-btn');
    const generateBtn = document.getElementById('generate-password');

    generateBtn.addEventListener('click', function () {
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 14; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        newPassword.value = password;
        confirmPassword.value = password;
        evaluatePassword();
    });

    function evaluatePassword() {
        const pass = newPassword.value;
        let strength = 0;
        let text = '';
        let color = '#eee';

        if (pass.length === 0) {
            text = 'Enter a password';
            strength = 0;
            color = '#eee';
        } else if (pass.length < 8) {
            text = 'Too short (min 8)';
            strength = 20;
            color = '#d63638';
        } else {
            if (/[a-z]/.test(pass)) strength += 20;
            if (/[A-Z]/.test(pass)) strength += 20;
            if (/[0-9]/.test(pass)) strength += 20;
            if (/[^a-zA-Z0-9]/.test(pass)) strength += 20;
            if (pass.length >= 12) strength += 20;

            if (strength >= 80) {
                text = 'Strong';
                color = '#46b450';
            } else if (strength >= 60) {
                text = 'Medium';
                color = '#ffb900';
            } else {
                text = 'Weak';
                color = '#d63638';
            }
        }

        strengthBar.style.width = strength + '%';
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = text;
        validateForm();
    }

    function validateForm() {
        const pass = newPassword.value;
        const confirm = confirmPassword.value;
        let valid = true;

        if (pass !== confirm) {
            document.getElementById('confirm-message').textContent = 'Passwords do not match';
            document.getElementById('confirm-message').style.color = '#d63638';
            valid = false;
        } else if (pass.length > 0) {
            document.getElementById('confirm-message').textContent = '✓ Matches';
            document.getElementById('confirm-message').style.color = '#46b450';
        } else {
            document.getElementById('confirm-message').textContent = '';
        }

        submitBtn.disabled = !(pass.length >= 8 && pass === confirm);
    }

    newPassword.addEventListener('input', evaluatePassword);
    confirmPassword.addEventListener('input', validateForm);



    // Password visibility toggles
document.getElementById('toggle-new-password').addEventListener('click', function () {
    const input = document.getElementById('new_password');
    const icon = this;
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '👁️‍🗨️'; // or '🙈'
    } else {
        input.type = 'password';
        icon.textContent = '👁️'; // or '👁️'
    }
});

document.getElementById('toggle-confirm-password').addEventListener('click', function () {
    const input = document.getElementById('confirm_password');
    const icon = this;
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '👁️‍🗨️';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
});

});
</script>

<style>
    #generate-password{
        padding: 10px 12px;
        border-radius: 0px;
        font-weight: normal;
        font-size: 16px;
    }
</style>