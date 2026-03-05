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
            'profile' => 'Edit Profile',
            'password' => 'Change Password',
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
        'title' => 'Edit Profile',
        'description' => 'Update your personal information.',
        'name' => 'Name',
        'email' => 'Email',
        'email_hint' => 'Email cannot be changed.',
        'phone' => 'Phone',
        'submit' => 'Save',
        'success' => 'Profile updated successfully.',
        'error' => 'An error occurred while updating profile.',
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
];
