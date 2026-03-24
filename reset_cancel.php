<?php
/*
 * reset_cancel.php — Clears the password-reset session (used when going back to Step 1)
 * Called silently from JS before redirecting to forgot_password.php
 */
session_start();
foreach (['pw_reset_step','pw_reset_username','pw_reset_email','pw_reset_otp','pw_reset_otp_at','pw_reset_attempts','pw_reset_resends'] as $k) {
    unset($_SESSION[$k]);
}
http_response_code(204);
exit;
