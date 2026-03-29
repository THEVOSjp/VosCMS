<?php

/**
 * My Page translations - English
 */

return [
    // Main
    'title' => 'My Page',
    'welcome' => 'Welcome, :name!',

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'reservations' => 'My Reservations',
        'profile' => 'Profile',
        'password' => 'Change Password',
        'logout' => 'Logout',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming' => 'Upcoming Reservations',
        'recent' => 'Recent Reservations',
        'no_upcoming' => 'No upcoming reservations.',
        'no_recent' => 'No recent reservations.',
        'view_all' => 'View All',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Reservation History',
        'filter' => [
            'all' => 'All',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ],
        'no_reservations' => 'No reservations found.',
        'booking_code' => 'Booking Code',
        'service' => 'Service',
        'date' => 'Date',
        'status' => 'Status',
        'actions' => 'Actions',
        'view' => 'View',
        'cancel' => 'Cancel',
    ],

    // Profile
    'profile' => [
        'title' => 'Profile Settings',
        'info' => 'Basic Information',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'save' => 'Save',
        'success' => 'Profile updated successfully.',
    ],

    // Password
    'password' => [
        'title' => 'Change Password',
        'current' => 'Current Password',
        'new' => 'New Password',
        'confirm' => 'Confirm New Password',
        'change' => 'Change Password',
        'success' => 'Password changed successfully.',
        'mismatch' => 'Current password is incorrect.',
    ],

    // Stats
    'stats' => [
        'total_bookings' => 'Total Bookings',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'upcoming' => 'Upcoming',
    ],
];
