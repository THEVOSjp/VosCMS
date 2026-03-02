<?php

/**
 * Admin translations - English
 */

return [
    // Common
    'title' => 'Admin',
    'dashboard' => 'Dashboard',
    'back_to_site' => 'Back to Site',
    'dark_mode' => 'Toggle Dark Mode',

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'reservations' => 'Reservations',
        'services' => 'Services',
        'categories' => 'Categories',
        'time_slots' => 'Time Slots',
        'members' => 'Members',
        'points' => 'Points',
        'users' => 'Users',
        'settings' => 'Settings',
        'site_management' => 'Site Management',
        'menu_management' => 'Menu Management',
        'design_management' => 'Design Management',
        'page_management' => 'Page Management',
    ],

    // Dashboard
    'stats' => [
        'today_reservations' => "Today's Reservations",
        'pending_reservations' => 'Pending Reservations',
        'monthly_revenue' => 'Monthly Revenue',
        'active_services' => 'Active Services',
        'total_users' => 'Total Users',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Reservation Management',
        'list' => 'Reservation List',
        'calendar' => 'Calendar View',
        'statistics' => 'Statistics',
        'create' => 'Add Reservation',
        'edit' => 'Edit Reservation',
        'detail' => 'Reservation Details',

        'filter' => [
            'all' => 'All',
            'today' => 'Today',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
        ],

        'actions' => [
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
            'complete' => 'Complete',
            'no_show' => 'No Show',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],

        'confirm_msg' => 'Confirm this reservation?',
        'cancel_msg' => 'Cancel this reservation?',
        'complete_msg' => 'Mark as completed?',
        'noshow_msg' => 'Mark as no-show?',

        'success' => [
            'created' => 'Reservation created.',
            'updated' => 'Reservation updated.',
            'confirmed' => 'Reservation confirmed.',
            'cancelled' => 'Reservation cancelled.',
            'completed' => 'Reservation marked as completed.',
        ],
    ],

    // Services
    'services' => [
        'title' => 'Service Management',
        'list' => 'Service List',
        'create' => 'Add Service',
        'edit' => 'Edit Service',
        'detail' => 'Service Details',

        'fields' => [
            'name' => 'Service Name',
            'slug' => 'URL Slug',
            'description' => 'Description',
            'short_description' => 'Short Description',
            'duration' => 'Duration (min)',
            'price' => 'Price',
            'category' => 'Category',
            'is_active' => 'Active',
            'max_capacity' => 'Max Capacity',
            'buffer_time' => 'Buffer Time (min)',
            'advance_booking_days' => 'Advance Booking (days)',
            'min_notice_hours' => 'Min Notice (hours)',
        ],

        'success' => [
            'created' => 'Service created.',
            'updated' => 'Service updated.',
            'deleted' => 'Service deleted.',
            'activated' => 'Service activated.',
            'deactivated' => 'Service deactivated.',
        ],

        'error' => [
            'has_reservations' => 'Cannot delete service with existing reservations.',
        ],
    ],

    // Categories
    'categories' => [
        'title' => 'Category Management',
        'list' => 'Category List',
        'create' => 'Add Category',
        'edit' => 'Edit Category',

        'fields' => [
            'name' => 'Category Name',
            'slug' => 'URL Slug',
            'description' => 'Description',
            'parent' => 'Parent Category',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
        ],

        'success' => [
            'created' => 'Category created.',
            'updated' => 'Category updated.',
            'deleted' => 'Category deleted.',
        ],
    ],

    // Time Slots
    'time_slots' => [
        'title' => 'Time Slot Management',
        'default_slots' => 'Default Time Slots',
        'blocked_dates' => 'Blocked Dates',

        'fields' => [
            'day_of_week' => 'Day of Week',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'max_bookings' => 'Max Bookings',
            'service' => 'Service',
            'specific_date' => 'Specific Date',
        ],

        'block_date' => 'Block Date',
        'unblock_date' => 'Unblock Date',
    ],

    // Users
    'users' => [
        'title' => 'User Management',
        'list' => 'User List',
        'create' => 'Add User',
        'edit' => 'Edit User',
        'detail' => 'User Details',

        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'role' => 'Role',
            'is_active' => 'Active',
            'created_at' => 'Registered',
            'last_login' => 'Last Login',
        ],

        'roles' => [
            'user' => 'User',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin',
        ],
    ],

    // Settings
    'settings' => [
        'title' => 'System Settings',
        'general' => 'General',
        'booking' => 'Booking',
        'email' => 'Email',
        'payment' => 'Payment',

        // Settings Tabs
        'tabs' => [
            'general' => 'General',
            'seo' => 'SEO',
            'pwa' => 'PWA',
            'system' => 'System',
        ],

        // Admin Path
        'admin_path' => [
            'title' => 'Admin Access Path',
            'description' => 'Change the admin page access path for security.',
            'current_url' => 'Current Access URL',
            'label' => 'Admin Path',
            'hint' => 'Only letters, numbers, hyphens (-), and underscores (_) are allowed.',
            'warning' => 'You must access the new address after changing the path.',
            'button' => 'Change Path',
            'changed' => 'Admin path has been changed. You are now accessing via the new path.',
            'error_empty' => 'Please enter the admin path.',
            'error_invalid' => 'Admin path can only contain letters, numbers, hyphens, and underscores.',
            'error_reserved' => 'Reserved paths cannot be used.',
        ],

        // Site Settings
        'site' => [
            'title' => 'Site Settings',
            'category_label' => 'Site Category (Business Type)',
            'category_description' => 'Select the business type for the reservation system. Optimized features will be provided based on the type.',
            'category_placeholder' => '-- Select a business type --',
            'categories' => [
                'beauty_salon' => 'Beauty Salon / Hair Salon',
                'nail_salon' => 'Nail Salon',
                'skincare' => 'Skincare / Esthetics',
                'massage' => 'Massage / Spa',
                'hospital' => 'Hospital / Clinic',
                'dental' => 'Dental Clinic',
                'studio' => 'Studio / Photo Studio',
                'restaurant' => 'Restaurant / Cafe',
                'accommodation' => 'Accommodation / Hotel / Pension',
                'sports' => 'Sports / Fitness / Golf',
                'education' => 'Education / Academy / Lessons',
                'consulting' => 'Consulting / Counseling',
                'pet' => 'Pet Services / Veterinary',
                'car' => 'Auto Repair / Car Wash',
                'other' => 'Other',
            ],
            'name' => 'Site Name',
            'tagline' => 'Site Tagline',
            'tagline_hint' => 'Enter a slogan or short description for your site.',
            'url' => 'Site URL',
        ],

        // Multilingual Input
        'multilang' => [
            'button_title' => 'Multilingual Input',
            'modal_title' => 'Multilingual Input',
            'modal_description' => 'Enter content for each language.',
            'tab_ko' => 'Korean',
            'tab_en' => 'English',
            'tab_ja' => 'Japanese',
            'placeholder' => 'Enter content...',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'saved' => 'Multilingual content has been saved.',
            'error' => 'An error occurred while saving.',
        ],

        // Logo Settings
        'logo' => [
            'title' => 'Logo Settings',
            'type_label' => 'Logo Display Type',
            'type_text' => 'Text Only',
            'type_image' => 'Image Only',
            'type_image_text' => 'Image + Text',
            'image_label' => 'Logo Image',
            'current' => 'Current Logo',
            'preview' => 'New Logo Preview',
            'display_preview' => 'Display Preview',
            'hint' => 'Supports JPG, PNG, GIF, SVG, WebP (Recommended height: 40px)',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete the logo image?',
        ],

        // SEO Settings
        'seo' => [
            'title' => 'SEO Settings',
            'description' => 'Manage search engine optimization settings.',

            // Meta Tags
            'meta' => [
                'title' => 'Meta Tags',
                'description_label' => 'Meta Description',
                'description_hint' => 'Description shown in search results. 150-160 characters recommended.',
                'keywords_label' => 'Meta Keywords',
                'keywords_hint' => 'Separate with commas (e.g., reservation, beauty, hair salon)',
                'keywords_placeholder' => 'reservation, beauty, hair salon, nail',
            ],

            // Open Graph
            'og' => [
                'title' => 'Social Media (Open Graph)',
                'description' => 'Set information displayed when sharing on social media.',
                'image_label' => 'Featured Image (OG Image)',
                'image_hint' => 'Recommended size: 1200x630 pixels (JPG, PNG, WebP)',
                'image_current' => 'Current Image',
                'image_preview' => 'New Image Preview',
                'image_delete' => 'Delete',
                'image_delete_confirm' => 'Delete the featured image?',
            ],

            // Search Engine
            'search_engine' => [
                'title' => 'Search Engine Settings',
                'robots_label' => 'Search Engine Visibility',
                'robots_index' => 'Allow indexing (index, follow)',
                'robots_noindex' => 'Disallow indexing (noindex, nofollow)',
                'robots_hint' => 'Set whether your site appears in search engine results.',
            ],

            // Webmaster Tools
            'webmaster' => [
                'title' => 'Webmaster Tools Verification',
                'google_label' => 'Google Search Console',
                'google_hint' => 'Enter the content value of the Google Search Console meta tag.',
                'google_placeholder' => 'XXXXXXXXXXXXXXXX',
                'naver_label' => 'Naver Webmaster Tools',
                'naver_hint' => 'Enter the content value of the Naver Webmaster Tools meta tag.',
                'naver_placeholder' => 'XXXXXXXXXXXXXXXX',
            ],

            // Analytics
            'analytics' => [
                'title' => 'Analytics Integration',
                'ga_label' => 'Google Analytics Tracking ID',
                'ga_hint' => 'Enter in G-XXXXXXXXXX or UA-XXXXXXXXX-X format.',
                'ga_placeholder' => 'G-XXXXXXXXXX',
                'gtm_label' => 'Google Tag Manager ID',
                'gtm_hint' => 'Enter in GTM-XXXXXXX format.',
                'gtm_placeholder' => 'GTM-XXXXXXX',
            ],

            // Save Message
            'success' => 'SEO settings saved.',
        ],

        // PWA Settings
        'pwa' => [
            'title' => 'PWA Settings',
            'description' => 'Manage Progressive Web App (PWA) settings.',

            // Frontend PWA
            'front' => [
                'title' => 'Frontend PWA',
                'description' => 'Web app settings for users.',
                'name_label' => 'App Name',
                'name_placeholder' => 'Enter app name',
                'short_name_label' => 'Short Name',
                'short_name_placeholder' => 'Short name',
                'short_name_hint' => 'Max 12 characters. Shown on home screen when space is limited.',
                'description_label' => 'App Description',
                'theme_color_label' => 'Theme Color',
                'bg_color_label' => 'Background Color',
                'display_label' => 'Display Mode',
                'icon_label' => 'App Icon',
            ],

            // Admin PWA
            'admin' => [
                'title' => 'Admin PWA',
                'description' => 'Web app settings for administrators.',
                'name_label' => 'App Name',
                'short_name_label' => 'Short Name',
                'theme_color_label' => 'Theme Color',
                'bg_color_label' => 'Background Color',
                'icon_label' => 'App Icon',
            ],

            // Common
            'icon_current' => 'Current Icon',
            'icon_hint' => 'PNG or WebP format, 512x512 pixels recommended',
            'icon_delete' => 'Delete Icon',
            'icon_delete_confirm' => 'Are you sure you want to delete this icon?',
            'icon_deleted' => 'Icon has been deleted.',
            'error_icon_type' => 'Invalid image format. Only PNG or WebP allowed.',
            'success' => 'PWA settings saved.',
        ],

        // System Information
        'system' => [
            // Tab Menu
            'tabs' => [
                'info' => 'Information',
                'cache' => 'Cache',
                'mode' => 'Mode',
                'logs' => 'Logs',
                'updates' => 'Updates',
            ],
            'app' => [
                'title' => 'Application Information',
                'name' => 'App Name',
                'version' => 'Version',
                'environment' => 'Environment',
                'debug_mode' => 'Debug Mode',
                'debug_warning' => 'Disable debug mode in production environment.',
                'url' => 'URL',
                'locale' => 'Locale',
            ],
            'php' => [
                'title' => 'PHP Information',
                'version' => 'PHP Version',
                'sapi' => 'SAPI',
                'timezone' => 'Timezone',
                'memory_limit' => 'Memory Limit',
                'max_execution_time' => 'Max Execution Time',
                'upload_max_filesize' => 'Max Upload Size',
                'post_max_size' => 'Max POST Size',
                'display_errors' => 'Display Errors',
                'extensions' => 'Required Extensions',
            ],
            'db' => [
                'title' => 'Database Information',
                'driver' => 'Driver',
                'version' => 'Version',
                'host' => 'Host',
                'database' => 'Database',
                'charset' => 'Charset',
                'collation' => 'Collation',
            ],
            'server' => [
                'title' => 'Server Information',
                'os' => 'Operating System',
                'os_family' => 'OS Family',
                'software' => 'Server Software',
                'document_root' => 'Document Root',
                'current_time' => 'Current Time',
            ],
            'status' => [
                'on' => 'On',
                'off' => 'Off',
            ],
            // Cache Management
            'cache' => [
                'title' => 'Cache Management',
                'description' => 'Manage application cache. Clearing cache may temporarily affect performance.',
                'view' => 'View Cache',
                'view_desc' => 'Compiled view template cache',
                'config' => 'Config Cache',
                'config_desc' => 'Application configuration cache',
                'route' => 'Route Cache',
                'route_desc' => 'Routing information cache',
                'clear' => 'Clear',
                'clear_all' => 'Clear All Cache',
                'cached' => 'Cached',
                'not_cached' => 'None',
                'confirm_clear' => 'Are you sure you want to clear the cache?',
                'cleared' => 'Cache has been cleared.',
            ],
            // Mode Management
            'mode' => [
                'title' => 'Mode Management',
                'description' => 'Manage application runtime modes.',
                'debug' => 'Debug Mode',
                'debug_desc' => 'Shows detailed error messages. Disable in production.',
                'maintenance' => 'Maintenance Mode',
                'maintenance_desc' => 'Blocks user access during site maintenance.',
                'environment' => 'Environment',
                'environment_desc' => 'Current application runtime environment',
                'env_notice' => 'Debug mode and environment can be changed in the .env file.',
                'enable_maintenance' => 'Enable Maintenance Mode',
                'disable_maintenance' => 'Disable Maintenance Mode',
                'confirm_enable_maintenance' => 'Enable maintenance mode? All users except administrators will be blocked from accessing the site.',
                'confirm_disable_maintenance' => 'Disable maintenance mode?',
                'maintenance_enabled' => 'Maintenance mode has been enabled.',
                'maintenance_disabled' => 'Maintenance mode has been disabled.',
                'maintenance_message' => 'The site is currently under maintenance. Please visit again later.',
                // Debug mode toggle
                'enable_debug' => 'Enable Debug Mode',
                'disable_debug' => 'Disable Debug Mode',
                'confirm_enable_debug' => 'Enable debug mode? Error details will be displayed.',
                'confirm_disable_debug' => 'Disable debug mode?',
                'debug_enabled' => 'Debug mode has been enabled.',
                'debug_disabled' => 'Debug mode has been disabled.',
                'debug_error' => 'An error occurred while setting debug mode.',
                'debug_env_locked' => 'Cannot disable because APP_DEBUG=true is set in .env file.',
            ],
            // Log Management
            'logs' => [
                'title' => 'Log Management',
                'description' => 'Manage application log files.',
                'filename' => 'Filename',
                'size' => 'Size',
                'modified' => 'Modified',
                'actions' => 'Actions',
                'view' => 'View',
                'delete' => 'Delete',
                'download' => 'Download',
                'copy' => 'Copy',
                'copied' => 'Copied to clipboard.',
                'clear_all' => 'Clear All Logs',
                'no_logs' => 'No log files found.',
                'no_logs_desc' => 'No logs have been recorded yet.',
                'confirm_delete' => 'Are you sure you want to delete this log file?',
                'confirm_clear_all' => 'Are you sure you want to delete all log files? This action cannot be undone.',
                'deleted' => 'Log file has been deleted.',
                'all_cleared' => 'All log files have been deleted.',
                'back_to_list' => 'Back to list',
                'selected' => 'selected',
                'delete_selected' => 'Delete Selected',
                'confirm_delete_selected' => 'Are you sure you want to delete selected log files?',
                'selected_deleted' => ':count log file(s) have been deleted.',
                'total_files' => 'Total :count file(s)',
                'last_lines' => 'Showing last :count lines',
                'showing_first' => 'Showing :count of :total files.',
            ],
            // Update Management
            'updates' => [
                'title' => 'Update Management',
                'description' => 'Manage system updates via GitHub.',
                'current_version' => 'Current Version',
                'channel' => 'Channel',
                'check_update' => 'Check for Updates',
                'checking' => 'Checking...',
                'up_to_date' => 'You are using the latest version.',
                'new_version_available' => 'New version available!',
                'view_details' => 'View Details',
                'release_notes' => 'Release Notes',
                'no_notes' => 'No release notes available.',
                'no_releases' => 'No releases found.',
                'github_settings' => 'GitHub Settings',
                'github_description' => 'Enter GitHub repository information to enable auto-updates.',
                'github_owner' => 'Repository Owner',
                'github_owner_hint' => 'GitHub username or organization',
                'github_repo' => 'Repository Name',
                'github_repo_hint' => 'Repository name (e.g., rezlyx)',
                'github_branch' => 'Branch',
                'github_token' => 'GitHub Token',
                'github_token_hint' => 'Personal Access Token required for private repositories',
                'github_not_configured' => 'GitHub repository is not configured.',
                'optional' => 'Optional',
                'settings_saved' => 'GitHub settings have been saved.',
                'settings_error' => 'Error saving settings.',
                'requirements' => 'System Requirements',
                'writable_root' => 'Root Directory Writable',
                'not_available' => 'N/A',
                'requirements_warning' => 'Some requirements are not met. Auto-update may be limited.',
                'notes_title' => 'Update Notes',
                'note_backup' => 'A backup is automatically created before updating.',
                'note_maintenance' => 'Site goes into maintenance mode during update.',
                'note_rollback' => 'Automatic rollback on update failure.',
                'note_private' => 'Private repositories require a GitHub Personal Access Token.',
            ],
        ],

        // System Info (legacy)
        'system_info' => [
            'title' => 'System Information',
            'php_version' => 'PHP Version',
            'environment' => 'Environment',
            'timezone' => 'Timezone',
            'debug_mode' => 'Debug Mode',
            'enabled' => 'Enabled',
            'disabled' => 'Disabled',
        ],

        'fields' => [
            'app_name' => 'Site Name',
            'app_timezone' => 'Timezone',
            'app_locale' => 'Default Language',
            'admin_path' => 'Admin Path',
            'booking_auto_confirm' => 'Auto Confirm Bookings',
            'booking_email_notification' => 'Email Notifications',
            'booking_advance_days' => 'Advance Booking (days)',
        ],

        'success' => 'Site settings saved.',
        'error_save' => 'Save failed',
        'error_image_type' => 'Invalid image format. (Only JPG, PNG, GIF, SVG, WebP allowed)',
        'logo_deleted' => 'Logo image has been deleted.',
    ],

    // Site Management
    'site' => [
        // Menu Management
        'menus' => [
            'title' => 'Menu Management',
            'description' => 'Manage site navigation menus.',
            'list' => 'Menu List',
            'add' => 'Add Menu',
            'coming_soon' => 'Menu management coming soon',
            'coming_soon_desc' => 'You will be able to manage navigation menus soon.',
        ],

        // Design Management
        'design' => [
            'title' => 'Design Management',
            'description' => 'Manage site design and themes.',
            'theme_title' => 'Theme Settings',
            'theme_desc' => 'Change the site color theme and style.',
            'layout_title' => 'Layout Settings',
            'layout_desc' => 'Change page layout and structure.',
            'header_footer_title' => 'Header/Footer',
            'header_footer_desc' => 'Change header and footer design.',
            'coming_soon' => 'Coming Soon',
        ],

        // Page Management
        'pages' => [
            'title' => 'Page Management',
            'description' => 'Create and manage custom pages.',
            'list' => 'Page List',
            'add' => 'New Page',
            'system_page' => 'System Page',
            'custom_page' => 'Custom Page',
            'empty' => 'No custom pages yet.',
            'empty_hint' => 'Add new pages to expand your site.',
            'home' => 'Home',
            'terms' => 'Terms of Service',
            'privacy' => 'Privacy Policy',
        ],
    ],

    // Common Buttons
    'buttons' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'add' => 'Add',
        'create' => 'Create',
        'update' => 'Update',
        'search' => 'Search',
        'reset' => 'Reset',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'close' => 'Close',
    ],

    // Common Messages
    'messages' => [
        'confirm_delete' => 'Are you sure you want to delete?',
        'no_data' => 'No data available.',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
    ],
];
