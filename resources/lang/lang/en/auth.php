<?php

/**
 * Authentication translations - English
 */

return [
    // Login
    'login' => [
        'title' => 'Login',
        'description' => 'Log in to your account to manage reservations',
        'email' => 'Email',
        'email_placeholder' => 'example@email.com',
        'password' => 'Password',
        'password_placeholder' => '••••••••',
        'remember' => 'Remember me',
        'forgot' => 'Forgot password?',
        'submit' => 'Login',
        'no_account' => "Don't have an account?",
        'register_link' => 'Register',
        'back_home' => '← Back to Home',
        'success' => 'Successfully logged in.',
        'failed' => 'Invalid email or password.',
        'required' => 'Please enter your email and password.',
        'error' => 'An error occurred during login.',
        'social_only' => 'This account was registered via social login. Please use social login.',
    ],

    // Register
    'register' => [
        'title' => 'Register',
        'description' => 'Start making reservations with RezlyX',
        'name' => 'Name',
        'name_placeholder' => 'John Doe',
        'email' => 'Email',
        'email_placeholder' => 'example@email.com',
        'phone' => 'Phone',
        'phone_placeholder' => '010-1234-5678',
        'phone_hint' => 'Select country code and enter your phone number',
        'password' => 'Password',
        'password_placeholder' => 'At least 12 characters',
        'password_hint' => 'Min 12 chars with uppercase, lowercase, number & special character',
        'password_confirm' => 'Confirm Password',
        'password_confirm_placeholder' => 'Re-enter password',
        'agree_terms' => ' I agree',
        'agree_privacy' => ' I agree',
        'submit' => 'Register',
        'has_account' => 'Already have an account?',
        'login_link' => 'Login',
        'success' => 'Registration completed successfully.',
        'success_login' => 'Go to Login',
        'email_exists' => 'This email is already registered.',
        'error' => 'An error occurred during registration.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Forgot Password',
        'description' => 'Enter your email address and we will send you a password reset link.',
        'email' => 'Email',
        'submit' => 'Send Reset Link',
        'back_login' => 'Back to Login',
        'success' => 'Password reset link has been sent to your email.',
        'not_found' => 'Email address not found.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Reset Password',
        'email' => 'Email',
        'password' => 'New Password',
        'password_confirm' => 'Confirm New Password',
        'submit' => 'Reset Password',
        'success' => 'Your password has been reset.',
        'invalid_token' => 'Invalid token.',
        'expired_token' => 'Token has expired.',
    ],

    // Logout
    'logout' => [
        'success' => 'Successfully logged out.',
    ],

    // Email verification
    'verify' => [
        'title' => 'Verify Email',
        'description' => 'We have sent a verification email to your address. Please check your email.',
        'resend' => 'Resend Verification Email',
        'success' => 'Email verified successfully.',
        'already_verified' => 'Email is already verified.',
    ],

    // Social login
    'social' => [
        'or' => 'or',
        'google' => 'Sign in with Google',
        'kakao' => 'Sign in with Kakao',
        'naver' => 'Sign in with Naver',
        'line' => 'Sign in with LINE',
    ],

    // Social login buttons
    'login_with_line' => 'Sign in with LINE',
    'login_with_google' => 'Sign in with Google',
    'login_with_kakao' => 'Sign in with Kakao',
    'login_with_naver' => 'Sign in with Naver',
    'login_with_apple' => 'Sign in with Apple',
    'login_with_facebook' => 'Sign in with Facebook',
    'or_continue_with' => 'or',

    // Terms Agreement
    'terms' => [
        'title' => 'Terms Agreement',
        'subtitle' => 'Please agree to the terms to use the service',
        'agree_all' => 'I agree to all terms',
        'required' => 'Required',
        'optional' => 'Optional',
        'required_mark' => 'Required',
        'required_note' => '* indicates required items',
        'required_alert' => 'Please agree to all required terms.',
        'notice' => 'You may not be able to use the service if you do not agree to the terms.',
        'view_content' => 'View content',
        'hide_content' => 'Hide content',
        'translation_pending' => 'Translation in progress',
    ],

    // My Page
    'mypage' => [
        'title' => 'My Page',
        'welcome' => 'Hello, :name!',
        'member_since' => 'Member since :date',
        'menu' => [
            'dashboard' => 'Dashboard',
            'reservations' => 'Reservations',
            'profile' => 'Profile',
            'settings' => 'Settings',
            'password' => 'Change Password',
            'withdraw' => 'Delete Account',
            'logout' => 'Logout',
        ],
        'stats' => [
            'total_reservations' => 'Total Reservations',
            'upcoming' => 'Upcoming',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ],
        'recent_reservations' => 'Recent Reservations',
        'no_reservations' => 'No reservations found.',
        'view_all' => 'View All',
        'quick_actions' => 'Quick Actions',
        'make_reservation' => 'Make a Reservation',
    ],

    // Profile
    'profile' => [
        'title' => 'Profile',
        'description' => 'My profile information.',
        'edit_title' => 'Edit Profile',
        'edit_description' => 'Edit your personal information.',
        'edit_button' => 'Edit',
        'name' => 'Name',
        'email' => 'Email',
        'email_hint' => 'Email cannot be changed.',
        'phone' => 'Phone',
        'not_set' => 'Not set',
        'submit' => 'Save',
        'success' => 'Profile updated successfully.',
        'error' => 'An error occurred while updating profile.',
    ],

    // Settings
    'settings' => [
        'title' => 'Privacy Settings',
        'description' => 'Choose which information to show to other users.',
        'info' => 'Disabled items will not be visible to other users. Name is always visible.',
        'success' => 'Settings saved successfully.',
        'error' => 'An error occurred while saving settings.',
        'no_fields' => 'No configurable fields available.',
        'fields' => [
            'email' => 'Email', 'email_desc' => 'Show your email address to other users.',
            'profile_photo' => 'Profile Photo', 'profile_photo_desc' => 'Show your profile photo to other users.',
            'phone' => 'Phone Number', 'phone_desc' => 'Show your phone number to other users.',
            'birth_date' => 'Date of Birth', 'birth_date_desc' => 'Show your date of birth to other users.',
            'gender' => 'Gender', 'gender_desc' => 'Show your gender to other users.',
            'company' => 'Company', 'company_desc' => 'Show your company to other users.',
            'blog' => 'Blog', 'blog_desc' => 'Show your blog URL to other users.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Change Password',
        'description' => 'Please change your password regularly for security.',
        'current' => 'Current Password',
        'current_placeholder' => 'Enter current password',
        'new' => 'New Password',
        'new_placeholder' => 'Enter new password',
        'confirm' => 'Confirm New Password',
        'confirm_placeholder' => 'Re-enter new password',
        'submit' => 'Change Password',
        'success' => 'Password changed successfully.',
        'error' => 'An error occurred while changing password.',
        'wrong_password' => 'Current password is incorrect.',
    ],

    // Delete Account
    'withdraw' => [
        'title' => 'Delete Account',
        'description' => 'Your personal information will be anonymized immediately upon account deletion. This action cannot be undone.',
        'warning_title' => 'Please read carefully before proceeding',
        'warnings' => [
            'account' => 'All personal information including name, email, phone number, date of birth, and profile photo will be immediately anonymized. You will no longer be identifiable.',
            'reservation' => 'If you have any active or upcoming reservations, please cancel them before deleting your account. Reservations cannot be modified or cancelled after account deletion.',
            'payment' => 'Payment and transaction records will be retained in anonymized form for the legally required retention period (5 years under Korean tax law, 7 years under Japanese tax law).',
            'recovery' => 'Deleted accounts cannot be recovered. You may re-register with the same email, but all previous data including reservations, points, and messages will not be restored.',
            'social' => 'If you registered via social login (Google, Kakao, LINE, etc.), the connection to that social service will also be removed.',
            'message' => 'All received messages and notification history will be permanently deleted.',
        ],
        'retention_notice' => '※ Transaction records required by applicable laws will be retained in a non-identifiable form for the legally mandated period, then permanently deleted.',
        'reason' => 'Reason for leaving',
        'reason_placeholder' => 'Please select a reason',
        'reasons' => [
            'not_using' => 'No longer using the service',
            'other_service' => 'Switching to another service',
            'dissatisfied' => 'Dissatisfied with the service',
            'privacy' => 'Privacy concerns',
            'too_many_emails' => 'Too many emails/notifications',
            'other' => 'Other',
        ],
        'reason_other' => 'Other reason',
        'reason_other_placeholder' => 'Please enter your reason',
        'password' => 'Confirm password',
        'password_placeholder' => 'Enter current password',
        'password_hint' => 'Please enter your current password to verify your identity.',
        'confirm_text' => 'I have read and understood all the above information, and I agree to the anonymization of my personal data and account deletion.',
        'submit' => 'Delete Account',
        'success' => 'Your account has been deleted. Thank you for using our service.',
        'wrong_password' => 'Incorrect password.',
        'error' => 'An error occurred while processing account deletion.',
        'confirm_required' => 'Please check the agreement to proceed.',
    ],
];
