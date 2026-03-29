<?php

/**
 * Booking translations - English
 */

return [
    // Page titles
    'title' => 'Book Now',
    'service_list' => 'Service List',
    'select_service' => 'Select Service',
    'select_date' => 'Select Date',
    'select_time' => 'Select Time',
    'enter_info' => 'Enter Information',
    'confirm_booking' => 'Confirm Booking',
    'confirm_info' => 'Please confirm your booking information',
    'complete_booking' => 'Complete Booking',
    'select_service_datetime' => 'Please select your service and preferred date/time',
    'staff_designation_guide' => 'For staff-designated bookings, please proceed from the staff page',
    'go_staff_booking' => 'Staff Designated Booking',
    'select_datetime' => 'Please select a date and time',
    'no_services' => 'No services currently available.',
    'contact_admin' => 'Please contact the administrator.',
    'notes' => 'Special Requests',
    'notes_placeholder' => 'Enter any special requests',
    'customer' => 'Customer',
    'phone' => 'Phone',
    'date_label' => 'Date',
    'time_label' => 'Time',
    'total_price' => 'Total Amount',
    'cancel_policy' => 'Cancellations are allowed up to 24 hours before the reservation time. A cancellation fee may apply for later cancellations.',
    'success' => 'Booking completed!',
    'success_desc' => 'A confirmation will be sent. Please keep your booking number below.',
    'submitting' => 'Processing...',
    'select_staff' => 'Select a staff member',
    'no_preference' => 'No preference',
    'staff' => 'Staff',
    'designation_fee' => 'Designation Fee',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Checking available times...',
    'no_available_slots' => 'No available times on the selected date.',
    'items_selected' => 'selected',
    'total_duration' => 'Total duration',

    // Steps
    'step' => [
        'service' => 'Select Service',
        'datetime' => 'Date/Time',
        'info' => 'Information',
        'confirm' => 'Confirm',
    ],

    // Service
    'service' => [
        'title' => 'Service',
        'name' => 'Service Name',
        'description' => 'Description',
        'duration' => 'Duration',
        'price' => 'Price',
        'category' => 'Category',
        'select' => 'Select',
        'view_detail' => 'View Details',
        'no_services' => 'No services available.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Booking Date',
        'select_date' => 'Please select a date',
        'available' => 'Available',
        'unavailable' => 'Unavailable',
        'fully_booked' => 'Fully Booked',
        'past_date' => 'Past Date',
    ],

    'time' => [
        'title' => 'Booking Time',
        'select_time' => 'Please select a time',
        'available_slots' => 'Available Time Slots',
        'no_slots' => 'No available time slots.',
        'remaining' => ':count spots remaining',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Name',
        'customer_email' => 'Email',
        'customer_phone' => 'Phone',
        'guests' => 'Number of Guests',
        'notes' => 'Special Requests',
        'notes_placeholder' => 'Enter any special requests',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Confirm Booking',
        'summary' => 'Booking Summary',
        'service_info' => 'Service Information',
        'booking_info' => 'Booking Information',
        'customer_info' => 'Customer Information',
        'total_price' => 'Total',
        'agree_terms' => 'I agree to the booking terms',
        'submit' => 'Complete Booking',
    ],

    // Complete
    'complete' => [
        'title' => 'Booking Complete',
        'success' => 'Your booking has been completed!',
        'booking_code' => 'Booking Code',
        'check_email' => 'A confirmation email has been sent to your email address.',
        'view_detail' => 'View Booking Details',
        'book_another' => 'Make Another Booking',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Find Booking',
        'description' => 'Enter your booking information to find your reservation.',
        'booking_code' => 'Booking Code',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'Email',
        'email_placeholder' => 'Email used for booking',
        'phone' => 'Phone Number',
        'phone_placeholder' => 'Phone number used for booking',
        'search' => 'Search',
        'search_method' => 'Search Method',
        'by_code' => 'Search by Booking Code',
        'by_email' => 'Search by Email',
        'by_phone' => 'Search by Phone',
        'not_found' => 'Booking not found. Please check your information.',
        'input_required' => 'Please enter a booking code and email or phone number.',
        'result_title' => 'Search Results',
        'multiple_results' => ':count bookings found.',
        'hint' => 'For accurate results, enter a booking code along with your email or phone number.',
        'help_text' => 'Can\'t find your booking?',
        'contact_support' => 'Contact Support',
    ],

    // Detail
    'detail' => [
        'title' => 'Booking Details',
        'status' => 'Status',
        'booking_date' => 'Date & Time',
        'service' => 'Service',
        'services' => 'Services',
        'bundle' => 'Bundle Package',
        'guests' => 'Guests',
        'total_price' => 'Total Price',
        'payment_status' => 'Payment Status',
        'notes' => 'Special Requests',
        'created_at' => 'Booked On',
        'duration_unit' => 'min',
        'staff_not_assigned' => 'Not assigned',
        'back_to_lookup' => 'Booking Lookup',
        'payment' => 'Payment Details',
        'total' => 'Subtotal',
        'discount' => 'Discount',
        'points_used' => 'Points Used',
        'final_amount' => 'Final Amount',
        'staff' => 'Staff',
        'designation_fee' => 'Designation Fee',
        'cancel_info' => 'Cancellation Details',
        'cancelled_at' => 'Cancelled On',
        'cancel_reason' => 'Cancellation Reason',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Cancel Booking',
        'confirm' => 'Are you sure you want to cancel this booking?',
        'reason' => 'Cancellation Reason',
        'reason_placeholder' => 'Please enter the reason for cancellation',
        'submit' => 'Cancel Booking',
        'success' => 'Your booking has been cancelled.',
        'cannot_cancel' => 'This booking cannot be cancelled.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Your booking has been received. Please wait for confirmation.',
        'confirmed' => 'Your booking has been confirmed.',
        'cancelled' => 'Your booking has been cancelled.',
        'completed' => 'Service completed.',
        'no_show' => 'Marked as no-show.',
    ],

    // Payment status
    'payment' => [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'refunded' => 'Refunded',
        'needs_payment' => 'Payment required',
        'needs_payment_desc' => 'Your reservation will be confirmed after payment.',
        'pay_now' => 'Pay Now',
        'charge_amount' => 'Amount to Pay',
        'back_to_detail' => 'Back to reservation details',
        'loading' => 'Loading payment...',
        'deposit' => 'Deposit',
        'deposit_notice' => 'Remaining balance will be paid on-site.',
        'retry' => 'Retry Payment',
        'cancel_reservation' => 'Cancel Reservation',
        'applied_price' => 'applied price',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Service not found.',
        'slot_unavailable' => 'The selected time slot is not available.',
        'past_date' => 'Cannot book for past dates.',
        'max_capacity' => 'Maximum capacity exceeded.',
        'booking_failed' => 'An error occurred while processing your booking.',
        'required_fields' => 'Please enter your name and contact information.',
        'invalid_service' => 'Invalid service.',
    ],

    'member_discount' => 'Member Discount',
    'use_points' => 'Use Points',
    'points_balance' => 'Balance',
    'use_all' => 'Use All',
    'points_default_name' => 'Points',
    'deposit_pay_now' => 'Deposit (Pay Now)',
    'deposit_remaining_later' => 'Remaining balance will be charged at service time',
    'next' => 'Next',
    'categories' => 'categories',
    'service_count' => 'services',
    'expected_points' => 'Expected Points',
    'reservation_complete' => 'Reservation Complete',
    'reservation_complete_desc' => 'Please check your reservation details',
    'reservation_number' => 'Reservation No.',
    'check_summary' => 'Check Details',
];
